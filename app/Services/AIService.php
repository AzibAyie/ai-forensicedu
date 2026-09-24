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

    public function evaluateReport(array $reportData, array $questions, string $caseScenario, array $simulatedEvidence = []): array
    {
        $questionsText = collect($questions)->map(fn ($q, $i) => ($i + 1).". Q: {$q['question']} (Max: {$q['marks']} marks)\n   A: ".($reportData['answers'][$q['id']] ?? 'No answer provided')
        )->join("\n\n");

        $evidenceJson = mb_substr(json_encode($simulatedEvidence, JSON_PRETTY_PRINT), 0, 4000);

        $prompt = "You are a strict digital forensics lecturer grading a student's investigation report against the ACTUAL evidence for this case. Do not give credit for confident-sounding writing that isn't backed by the evidence below - vague, generic, or unsupported answers must score LOW even if they read smoothly.

CASE SCENARIO:
{$caseScenario}

ACTUAL EVIDENCE FOR THIS CASE (use this to check whether each answer is factually correct - an answer that contradicts this evidence, invents facts not in it, or never engages with specifics from it should score near zero):
{$evidenceJson}

STUDENT REPORT:
Executive Summary: {$reportData['executive_summary']}
Findings: {$reportData['findings']}
Timeline Reconstruction: {$reportData['timeline_reconstruction']}
Recommendations: {$reportData['recommendations']}
Conclusion: {$reportData['conclusion']}

INVESTIGATION QUESTIONS & ANSWERS:
{$questionsText}

Grading rules - apply in this order for each question:
1. First check: is the answer empty, random/gibberish text, unrelated to the question, or does it contradict the evidence? If yes, score = 0. Stop here for this question.
2. If the answer is a genuine, on-topic attempt but cites no specific correct detail from the evidence (no real timestamp, username, IP, value, or count that actually appears in the evidence above), score = at most 30% of that question's marks.
3. If the answer cites SOME correct specific details from the evidence but is incomplete or has minor errors, score proportionally (40-80%).
4. Only a fully correct, specific, evidence-backed answer earns full marks.
- overall_score must equal the sum of question_scores (as a percentage of total marks), not a separate impression-based number.
- grade must be consistent with overall_score: A=90-100, B=75-89, C=60-74, D=45-59, F=below 45.

Respond ONLY with valid JSON (no markdown) in this exact structure - the values below are placeholders showing the expected TYPE, not a scoring target:
{
  \"overall_score\": \"<integer 0-100, calculated from question_scores>\",
  \"grade\": \"<letter grade consistent with overall_score>\",
  \"strengths\": [\"<specific strength, or omit if none>\"],
  \"weaknesses\": [\"<specific weakness>\"],
  \"detailed_feedback\": \"<comprehensive paragraph feedback for the student, referencing what the evidence actually shows>\",
  \"question_scores\": {\"<question_id>\": {\"score\": \"<integer>\", \"max\": \"<integer, the question's marks>\", \"comment\": \"<why this score - what evidence was or wasn't used correctly>\"}},
  \"improvement_suggestions\": [\"<specific actionable suggestion>\"]
}";

        return $this->request($prompt, 'report evaluation', 'medium');
    }

    public function generateHint(string $question, string $caseScenario, string $evidenceOverview): array
    {
        $prompt = "You are helping a first-year student who is stuck on one investigation question in a digital forensics exercise. Write a hint in VERY SIMPLE, PLAIN English - short sentences, everyday words, no jargon. Do not give the answer or say what they will find.

Write the hint as exactly two short sentences:
1. Where to look - name the specific evidence panel (e.g. \"audit logs\", \"network logs\", \"database records\", \"system info\") they should open.
2. What to do there - one simple, concrete action, like \"count how many times X happens\" or \"compare the time on the first and last entry\" or \"find the row where X changes\".

Avoid words like \"indicators\", \"correlate\", \"reconstruct\", \"vulnerabilities exploited\" - say it the way you'd explain it to a beginner.

CASE SCENARIO:
{$caseScenario}

AVAILABLE EVIDENCE OVERVIEW:
{$evidenceOverview}

QUESTION THE STUDENT IS STUCK ON:
{$question}

Respond ONLY with valid JSON (no markdown, no explanation) in this exact structure:
{
  \"hint\": \"Look at [evidence panel]. [One simple, concrete action to try there].\"
}";

        return $this->request($prompt, 'hint generation');
    }

    public function generateEvidenceFromDocument(string $documentText): array
    {
        $documentText = mb_substr($documentText, 0, 6000);

        $prompt = "You are a digital forensics educator. A lecturer has uploaded a document containing a case scenario and investigation questions, but no simulated evidence. Read the document below and:

1. Extract or write a short, specific case title.
2. Extract or summarize a 2-3 sentence description of the incident.
3. Extract the full scenario narrative as written in the document (clean up formatting, keep the substance).
4. Extract the investigation questions as written in the document, preserving their wording and order, but strip out any inline marks/points text (e.g. \"(20 marks)\") from the question wording itself - put that value in the separate \"marks\" field instead. If marks aren't stated for a question, split marks evenly across all questions so they total 100.
5. Extract or write learning objectives and investigation instructions. Even if the document already states them, reformat them as a clean numbered list with a real line break before each numbered item (1. / 2. / 3.) - do not return them as one run-on paragraph, even if that is how the source document's text extraction flattened them.
6. Generate NEW simulated evidence (audit logs, database records, network logs, system info) that a student could actually use to answer the extracted questions. The evidence must be internally consistent with the scenario and specific enough to support each question.

DOCUMENT:
{$documentText}

Respond ONLY with valid JSON (no markdown, no explanation) in this exact structure:
{
  \"title\": \"Case title\",
  \"description\": \"2-3 sentence overview of the incident\",
  \"scenario\": \"Detailed narrative of what happened, who is involved, what company/system was affected\",
  \"learning_objectives\": \"Numbered list of 4-5 learning objectives\",
  \"investigation_instructions\": \"Step-by-step instructions for students on how to investigate\",
  \"simulated_evidence\": {
    \"overview\": \"Brief description of available evidence\",
    \"audit_logs\": [
      {\"timestamp\": \"2024-03-15 02:14:33\", \"user\": \"john.doe\", \"action\": \"LOGIN_SUCCESS\", \"ip\": \"192.168.1.105\", \"details\": \"Successful login after 47 failed attempts\"}
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
    {\"question\": \"Question exactly as extracted from the document\", \"marks\": 20}
  ]
}";

        return $this->request($prompt, 'evidence generation from document');
    }

    private const MAX_ATTEMPTS = 3;

    private function request(string $prompt, string $context, string $reasoningEffort = 'low'): array
    {
        $this->lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $result = $this->callGroq($prompt, $context, $attempt, $reasoningEffort);

            if (! empty($result['data'])) {
                return $result['data'];
            }

            if (($result['status'] ?? null) === 429) {
                // Retrying immediately during a rate-limit window just burns more
                // of the same limited budget without helping.
                break;
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                Log::warning("AI {$context}: attempt {$attempt} failed ({$this->lastError}), retrying");
                usleep(500_000);
            }
        }

        return [];
    }

    /**
     * @return array{data: array, status?: int}
     */
    private function callGroq(string $prompt, string $context, int $attempt, string $reasoningEffort = 'low'): array
    {
        if ($this->proxyUrl === '' || $this->proxySecret === '') {
            Log::error("AI {$context}: proxy is not configured", [
                'attempt' => $attempt,
                'proxy_url_set' => $this->proxyUrl !== '',
                'proxy_secret_set' => $this->proxySecret !== '',
            ]);
            $this->lastError = 'The AI service is not configured on this server. Please contact the administrator.';

            return ['data' => []];
        }

        try {
            $response = Http::withHeaders([
                'X-Proxy-Secret' => $this->proxySecret,
                'content-type' => 'application/json',
            ])->timeout(25)->post($this->proxyUrl, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'reasoning_effort' => $reasoningEffort,
            ]);

            $finishReason = $response->json('choices.0.finish_reason');
            $usage = $response->json('usage');

            if (! $response->successful()) {
                $errorMessage = $response->status() === 429
                    ? 'The AI service is briefly at capacity. Please wait a few seconds and try again.'
                    : ($response->json('error.message') ?? 'AI service request failed.');
                Log::error("AI {$context} failed", [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'error_body' => $response->json('error') ?? $response->body(),
                    'finish_reason' => $finishReason,
                    'usage' => $usage,
                ]);
                $this->lastError = $errorMessage;

                return ['data' => [], 'status' => $response->status()];
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
            $this->lastError = 'Could not reach the AI service. Please try again.';

            return ['data' => []];
        } catch (\Exception $e) {
            Log::error("AI {$context}: unexpected error", ['attempt' => $attempt, 'message' => $e->getMessage()]);
            $this->lastError = 'An unexpected error occurred while contacting the AI service.';

            return ['data' => []];
        }
    }
}
