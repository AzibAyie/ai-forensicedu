<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $proxyUrl;

    private string $proxySecret;

    private string $model;

    private ?string $lastError = null;

    public function __construct()
    {
        $this->proxyUrl = config('services.groq.proxy_url', '');
        $this->proxySecret = config('services.groq.proxy_secret', '');
        $this->model = config('services.groq.model', 'openai/gpt-oss-120b');
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function generateCase(string $incidentType, string $difficulty, string $additionalContext = ''): array
    {
        $incidentLabel = match ($incidentType) {
            'unauthorized_modification' => 'Unauthorized Data Modification',
            'brute_force' => 'Brute Force Login Attack',
            'mass_deletion' => 'Mass Data Deletion',
            default => $incidentType,
        };

        $prompt = "You are a digital forensics educator. Generate a realistic forensic case scenario for students studying cybersecurity.

Incident Type: {$incidentLabel}
Difficulty: {$difficulty}
Additional Context: {$additionalContext}

Respond ONLY with valid JSON (no markdown, no explanation) in this exact structure:
{
  \"title\": \"Case title (creative and specific)\",
  \"description\": \"2-3 sentence overview of the incident\",
  \"scenario\": \"Detailed narrative of what happened, who is involved, what company/system was affected\",
  \"learning_objectives\": \"Numbered list of 4-5 learning objectives\",
  \"investigation_instructions\": \"Step-by-step instructions for students on how to investigate\",
  \"simulated_evidence\": {
    \"overview\": \"Brief description of available evidence\",
    \"audit_logs\": [
      {\"timestamp\": \"2024-03-15 02:14:33\", \"user\": \"john.doe\", \"action\": \"LOGIN_SUCCESS\", \"ip\": \"192.168.1.105\", \"details\": \"Successful login after 47 failed attempts\"},
      {\"timestamp\": \"2024-03-15 02:14:35\", \"user\": \"john.doe\", \"action\": \"RECORD_MODIFIED\", \"ip\": \"192.168.1.105\", \"details\": \"Modified salary record for employee ID 3847\"}
    ],
    \"database_records\": [
      {\"id\": 1, \"before\": \"value_before\", \"after\": \"value_changed\", \"field\": \"field_name\", \"table\": \"table_name\"}
    ],
    \"network_logs\": [
      {\"timestamp\": \"2024-03-15 01:58:12\", \"source_ip\": \"192.168.1.105\", \"event\": \"Multiple failed login attempts\", \"count\": 47}
    ],
    \"system_info\": {\"incident_date\": \"2024-03-15\", \"affected_system\": \"System name\", \"estimated_impact\": \"Impact description\"}
  },
  \"questions\": [
    {\"question\": \"What specific actions did the attacker take and in what order?\", \"marks\": 20},
    {\"question\": \"Identify the indicators of compromise (IOCs) present in the evidence.\", \"marks\": 20},
    {\"question\": \"What vulnerabilities were exploited in this attack?\", \"marks\": 20},
    {\"question\": \"Describe the timeline of events from first attempt to completion.\", \"marks\": 20},
    {\"question\": \"What security controls should be implemented to prevent this attack?\", \"marks\": 20}
  ]
}";

        return $this->request($prompt, 'case generation');
    }

    public function evaluateReport(array $reportData, array $questions, string $caseScenario): array
    {
        $questionsText = collect($questions)->map(fn ($q, $i) => ($i + 1).". Q: {$q['question']} (Max: {$q['marks']} marks)\n   A: ".($reportData['answers'][$q['id']] ?? 'No answer provided')
        )->join("\n\n");

        $prompt = "You are a digital forensics lecturer evaluating a student's forensic investigation report. Be fair, constructive, and specific.

CASE SCENARIO:
{$caseScenario}

STUDENT REPORT:
Executive Summary: {$reportData['executive_summary']}
Findings: {$reportData['findings']}
Timeline Reconstruction: {$reportData['timeline_reconstruction']}
Recommendations: {$reportData['recommendations']}
Conclusion: {$reportData['conclusion']}

INVESTIGATION QUESTIONS & ANSWERS:
{$questionsText}

Evaluate this report and respond ONLY with valid JSON (no markdown):
{
  \"overall_score\": 75,
  \"grade\": \"B\",
  \"strengths\": [\"Clear identification of the attack vector\", \"Good use of timeline\"],
  \"weaknesses\": [\"Missing IOC analysis\", \"Recommendations too vague\"],
  \"detailed_feedback\": \"Comprehensive paragraph feedback for the student\",
  \"question_scores\": {\"question_id\": {\"score\": 15, \"max\": 20, \"comment\": \"Good answer but missing timestamp correlation\"}},
  \"improvement_suggestions\": [\"Specific actionable suggestion 1\", \"Specific actionable suggestion 2\"]
}";

        return $this->request($prompt, 'report evaluation');
    }

    private const MAX_ATTEMPTS = 3;

    private function request(string $prompt, string $context): array
    {
        $this->lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $result = $this->callGroq($prompt, $context, $attempt);

            if (! empty($result['data'])) {
                return $result['data'];
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                Log::warning("AI {$context}: attempt {$attempt} failed ({$this->lastError}), retrying");
                usleep(700_000);
            }
        }

        return [];
    }

    /**
     * @return array{data: array}
     */
    private function callGroq(string $prompt, string $context, int $attempt): array
    {
        try {
            $response = Http::withHeaders([
                'X-Proxy-Secret' => $this->proxySecret,
                'content-type' => 'application/json',
            ])->timeout(90)->post($this->proxyUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $finishReason = $response->json('choices.0.finish_reason');
            $usage = $response->json('usage');

            if (! $response->successful()) {
                $errorMessage = $response->json('error.message') ?? 'AI service request failed.';
                Log::error("AI {$context} failed", [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'error_body' => $response->json('error') ?? $response->body(),
                    'finish_reason' => $finishReason,
                    'usage' => $usage,
                ]);
                $this->lastError = $errorMessage;

                return ['data' => []];
            }

            $text = $response->json('choices.0.message.content', '');

            $clean = preg_replace('/```json|```/', '', $text);
            $decoded = json_decode(trim($clean), true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                Log::warning("AI {$context}: invalid or empty JSON in response", [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'finish_reason' => $finishReason,
                    'usage' => $usage,
                    'json_error' => json_last_error_msg(),
                ]);
                $this->lastError = 'AI service returned an invalid response.';

                return ['data' => []];
            }

            Log::info("AI {$context} succeeded", [
                'attempt' => $attempt,
                'finish_reason' => $finishReason,
                'usage' => $usage,
            ]);

            return ['data' => $decoded];
        } catch (ConnectionException $e) {
            Log::error("AI {$context}: connection error", ['attempt' => $attempt, 'message' => $e->getMessage()]);
            $this->lastError = 'DEBUG connection error (attempt '.$attempt.'): '.$e->getMessage();

            return ['data' => []];
        } catch (\Exception $e) {
            Log::error("AI {$context}: unexpected error", ['attempt' => $attempt, 'message' => $e->getMessage()]);
            $this->lastError = 'DEBUG unexpected error (attempt '.$attempt.'): '.get_class($e).': '.$e->getMessage();

            return ['data' => []];
        }
    }
}
