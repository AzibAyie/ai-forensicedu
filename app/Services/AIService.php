<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-6';
    private string $baseUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    public function generateCase(string $incidentType, string $difficulty, string $additionalContext = ''): array
    {
        $incidentLabel = match($incidentType) {
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

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => $this->model,
                'max_tokens' => 3000,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text', '');
                $clean = preg_replace('/```json|```/', '', $content);
                return json_decode(trim($clean), true) ?? [];
            }

            Log::error('AI case generation failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('AI service error', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function evaluateReport(array $reportData, array $questions, string $caseScenario): array
    {
        $questionsText = collect($questions)->map(fn($q, $i) =>
            ($i + 1) . ". Q: {$q['question']} (Max: {$q['marks']} marks)\n   A: " . ($reportData['answers'][$q['id']] ?? 'No answer provided')
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

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post($this->baseUrl, [
                'model' => $this->model,
                'max_tokens' => 2000,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text', '');
                $clean = preg_replace('/```json|```/', '', $content);
                return json_decode(trim($clean), true) ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('AI evaluation error', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
