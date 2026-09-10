<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseQuestion;
use App\Models\ForensicCase;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CaseController extends Controller
{
    public function create()
    {
        return view('lecturer.case.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'incident_type' => 'required|in:unauthorized_modification,brute_force,mass_deletion',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'description' => 'required|string',
            'scenario' => 'required|string',
            'learning_objectives' => 'required|string',
            'investigation_instructions' => 'required|string',
            'expected_duration' => 'required|integer|min:15|max:300',
            'password' => 'nullable|string|min:4',
            'publish_at' => 'nullable|date',
            'close_at' => 'nullable|date|after:publish_at',
            'question_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'timeline_events' => 'nullable|array',
            'timeline_events.*.timestamp' => 'nullable|string|max:60',
            'timeline_events.*.event' => 'nullable|string|max:500',
            'custom_logs' => 'nullable|array',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.marks' => 'required|integer|min:1',
        ]);

        $evidenceData = $this->buildSimulatedEvidence($request->incident_type);

        // Lecturer-authored audit log lines are appended to the generated set.
        if ($request->filled('custom_logs')) {
            foreach ($request->custom_logs as $log) {
                if (empty($log['details'])) continue;
                $evidenceData['audit_logs'][] = [
                    'timestamp' => $log['timestamp'] ?? '',
                    'user' => $log['user'] ?? 'unknown',
                    'action' => $log['action'] ?? 'EVENT',
                    'ip' => $log['ip'] ?? '',
                    'details' => $log['details'],
                ];
            }
        }

        $timeline = collect($request->input('timeline_events', []))
            ->filter(fn ($e) => ! empty($e['event']))
            ->values()->all();

        $pdfPath = $pdfName = null;
        if ($request->hasFile('question_pdf')) {
            $pdfPath = $request->file('question_pdf')->store('questions', 'local');
            $pdfName = $request->file('question_pdf')->getClientOriginalName();
        }

        $case = ForensicCase::create([
            'lecturer_id' => auth()->id(),
            'title' => $request->title,
            'incident_type' => $request->incident_type,
            'difficulty' => $request->difficulty,
            'description' => $request->description,
            'scenario' => $request->scenario,
            'learning_objectives' => $request->learning_objectives,
            'investigation_instructions' => $request->investigation_instructions,
            'simulated_evidence' => $evidenceData,
            'timeline_events' => $timeline ?: null,
            'question_pdf_path' => $pdfPath,
            'question_pdf_name' => $pdfName,
            'publish_at' => $request->publish_at,
            'close_at' => $request->close_at,
            'password' => $request->password,
            'is_locked' => !empty($request->password),
            'is_published' => $request->has('publish'),
            'expected_duration' => $request->expected_duration,
            'total_marks' => array_sum(array_column($request->questions, 'marks')),
        ]);

        foreach ($request->questions as $i => $q) {
            CaseQuestion::create([
                'forensic_case_id' => $case->id,
                'question' => $q['question'],
                'marks' => $q['marks'],
                'display_order' => $i + 1,
            ]);
        }

        ActivityLog::record('CASE_CREATED', "Created case: {$case->title}", $case->id);

        return redirect()->route('lecturer.dashboard')
            ->with('success', 'Case created successfully!');
    }

    public function edit(ForensicCase $forensicCase)
    {
        $this->authorizeCase($forensicCase);
        return view('lecturer.case.edit', compact('forensicCase'));
    }

    public function update(Request $request, ForensicCase $forensicCase)
    {
        $this->authorizeCase($forensicCase);
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'scenario' => 'required|string',
            'password' => 'nullable|string|min:4',
            'publish_at' => 'nullable|date',
            'close_at' => 'nullable|date|after:publish_at',
            'question_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'scenario' => $request->scenario,
            'password' => $request->password ?: $forensicCase->password,
            'is_locked' => $request->has('is_locked'),
            'is_published' => $request->has('is_published'),
            'publish_at' => $request->publish_at,
            'close_at' => $request->close_at,
        ];

        if ($request->hasFile('question_pdf')) {
            if ($forensicCase->question_pdf_path) {
                Storage::disk('local')->delete($forensicCase->question_pdf_path);
            }
            $data['question_pdf_path'] = $request->file('question_pdf')->store('questions', 'local');
            $data['question_pdf_name'] = $request->file('question_pdf')->getClientOriginalName();
        }

        $forensicCase->update($data);

        return redirect()->route('lecturer.dashboard')->with('success', 'Case updated.');
    }

    public function destroy(ForensicCase $forensicCase)
    {
        $this->authorizeCase($forensicCase);
        $title = $forensicCase->title;
        $forensicCase->delete();
        ActivityLog::record('CASE_DELETED', "Deleted case: {$title}");
        return redirect()->route('lecturer.dashboard')->with('success', 'Case deleted.');
    }

    public function toggleLock(ForensicCase $forensicCase)
    {
        $this->authorizeCase($forensicCase);
        $forensicCase->update(['is_locked' => !$forensicCase->is_locked]);
        $status = $forensicCase->is_locked ? 'locked' : 'unlocked';
        return back()->with('success', "Case {$status}.");
    }

    public function togglePublish(ForensicCase $forensicCase)
    {
        $this->authorizeCase($forensicCase);
        $forensicCase->update(['is_published' => !$forensicCase->is_published]);
        $status = $forensicCase->is_published ? 'published' : 'unpublished';
        return back()->with('success', "Case {$status}.");
    }

    public function generateAI(Request $request)
    {
        $request->validate([
            'incident_type' => 'required|in:unauthorized_modification,brute_force,mass_deletion',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'context' => 'nullable|string|max:500',
        ]);

        $ai = new AIService();
        $generated = $ai->generateCase($request->incident_type, $request->difficulty, $request->context ?? '');

        if (empty($generated)) {
            return response()->json(['success' => false, 'message' => 'AI generation failed. Please fill in manually.'], 422);
        }

        return response()->json(['success' => true, 'data' => $generated]);
    }

    public function downloadQuestionPdf(ForensicCase $forensicCase)
    {
        $this->authorizeCase($forensicCase);
        abort_unless($forensicCase->question_pdf_path, 404);
        return Storage::disk('local')->download(
            $forensicCase->question_pdf_path,
            $forensicCase->question_pdf_name ?? 'questions.pdf'
        );
    }

    private function authorizeCase(ForensicCase $forensicCase): void
    {
        if ($forensicCase->lecturer_id !== auth()->id()) {
            abort(403);
        }
    }

    private function buildSimulatedEvidence(string $type): array
    {
        return match($type) {
            'brute_force' => [
                'type' => 'brute_force',
                'overview' => 'Network and authentication logs showing repeated failed login attempts followed by successful unauthorized access.',
                'audit_logs' => [
                    ['timestamp' => '2024-03-15 01:58:12', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid password for admin@company.com'],
                    ['timestamp' => '2024-03-15 01:58:13', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid password for admin@company.com'],
                    ['timestamp' => '2024-03-15 01:58:14', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid password for admin@company.com'],
                    ['timestamp' => '2024-03-15 02:14:33', 'user' => 'admin', 'action' => 'LOGIN_SUCCESS', 'ip' => '192.168.1.105', 'details' => 'Successful login after 47 failed attempts'],
                    ['timestamp' => '2024-03-15 02:14:45', 'user' => 'admin', 'action' => 'FILE_ACCESSED', 'ip' => '192.168.1.105', 'details' => 'Accessed /confidential/payroll_Q1_2024.xlsx'],
                    ['timestamp' => '2024-03-15 02:15:01', 'user' => 'admin', 'action' => 'FILE_DOWNLOADED', 'ip' => '192.168.1.105', 'details' => 'Downloaded payroll_Q1_2024.xlsx (2.3MB)'],
                    ['timestamp' => '2024-03-15 02:16:22', 'user' => 'admin', 'action' => 'LOGOUT', 'ip' => '192.168.1.105', 'details' => 'Session terminated'],
                ],
                'network_logs' => [
                    ['timestamp' => '2024-03-15 01:58:12', 'source_ip' => '192.168.1.105', 'dest_ip' => '10.0.0.5', 'event' => 'Automated login attempt burst detected', 'request_rate' => '1 req/sec for 978 seconds'],
                    ['timestamp' => '2024-03-15 02:14:33', 'source_ip' => '192.168.1.105', 'dest_ip' => '10.0.0.5', 'event' => 'Authentication succeeded', 'note' => 'IP not in whitelist'],
                ],
                'system_info' => ['incident_date' => '2024-03-15', 'affected_system' => 'Company HR Portal', 'total_failed_attempts' => 47, 'time_to_breach' => '16 minutes 21 seconds', 'data_exfiltrated' => 'payroll_Q1_2024.xlsx (2.3MB)'],
            ],
            'unauthorized_modification' => [
                'type' => 'unauthorized_modification',
                'overview' => 'Database audit records showing unauthorized changes to financial records by a privileged insider.',
                'audit_logs' => [
                    ['timestamp' => '2024-06-10 22:47:01', 'user' => 'john.smith', 'action' => 'DB_CONNECT', 'ip' => '10.0.1.22', 'details' => 'Connected to payroll_db as db_admin'],
                    ['timestamp' => '2024-06-10 22:47:15', 'user' => 'john.smith', 'action' => 'RECORD_READ', 'ip' => '10.0.1.22', 'details' => 'SELECT * FROM employees WHERE id = 1042'],
                    ['timestamp' => '2024-06-10 22:47:31', 'user' => 'john.smith', 'action' => 'RECORD_MODIFIED', 'ip' => '10.0.1.22', 'details' => 'UPDATE employees SET salary=85000 WHERE id=1042 (was: 52000)'],
                    ['timestamp' => '2024-06-10 22:48:05', 'user' => 'john.smith', 'action' => 'RECORD_MODIFIED', 'ip' => '10.0.1.22', 'details' => 'UPDATE employees SET bank_account=\'MY-NEW-ACCT-9981\' WHERE id=1042'],
                    ['timestamp' => '2024-06-10 22:48:44', 'user' => 'john.smith', 'action' => 'RECORD_MODIFIED', 'ip' => '10.0.1.22', 'details' => 'UPDATE audit_trail SET modified_by=NULL WHERE record_id=1042'],
                    ['timestamp' => '2024-06-10 22:49:12', 'user' => 'john.smith', 'action' => 'DB_DISCONNECT', 'ip' => '10.0.1.22', 'details' => 'Session closed'],
                ],
                'database_records' => [
                    ['id' => 1042, 'field' => 'salary', 'table' => 'employees', 'before' => 'RM 52,000', 'after' => 'RM 85,000', 'changed_at' => '2024-06-10 22:47:31'],
                    ['id' => 1042, 'field' => 'bank_account', 'table' => 'employees', 'before' => 'MY-ORIG-ACCT-4421', 'after' => 'MY-NEW-ACCT-9981', 'changed_at' => '2024-06-10 22:48:05'],
                    ['id' => 1042, 'field' => 'modified_by', 'table' => 'audit_trail', 'before' => 'john.smith', 'after' => 'NULL', 'changed_at' => '2024-06-10 22:48:44'],
                ],
                'system_info' => ['incident_date' => '2024-06-10', 'affected_system' => 'HR Payroll Database', 'affected_employee_id' => 1042, 'financial_impact' => 'RM 33,000/year salary inflation + diverted payments'],
            ],
            'mass_deletion' => [
                'type' => 'mass_deletion',
                'overview' => 'System logs indicating bulk deletion of customer transaction records during off-hours.',
                'audit_logs' => [
                    ['timestamp' => '2024-08-22 23:01:15', 'user' => 'sys_admin', 'action' => 'DB_CONNECT', 'ip' => '172.16.0.8', 'details' => 'Connected to transactions_db'],
                    ['timestamp' => '2024-08-22 23:01:33', 'user' => 'sys_admin', 'action' => 'QUERY_EXECUTED', 'ip' => '172.16.0.8', 'details' => 'DELETE FROM transactions WHERE date < \'2024-01-01\''],
                    ['timestamp' => '2024-08-22 23:01:34', 'user' => 'sys_admin', 'action' => 'BULK_DELETE', 'ip' => '172.16.0.8', 'details' => '847 records deleted in 1.2 seconds'],
                    ['timestamp' => '2024-08-22 23:01:40', 'user' => 'sys_admin', 'action' => 'QUERY_EXECUTED', 'ip' => '172.16.0.8', 'details' => 'DELETE FROM transaction_backups WHERE backup_date < \'2024-01-01\''],
                    ['timestamp' => '2024-08-22 23:01:42', 'user' => 'sys_admin', 'action' => 'BULK_DELETE', 'ip' => '172.16.0.8', 'details' => '847 backup records deleted'],
                    ['timestamp' => '2024-08-22 23:02:01', 'user' => 'sys_admin', 'action' => 'DB_DISCONNECT', 'ip' => '172.16.0.8', 'details' => 'Session closed'],
                ],
                'database_records' => [
                    ['summary' => 'Records deleted', 'count' => 847, 'table' => 'transactions', 'date_range' => '2023-01-01 to 2023-12-31', 'total_value' => 'RM 4,238,591.20'],
                    ['summary' => 'Backup records deleted', 'count' => 847, 'table' => 'transaction_backups', 'date_range' => '2023-01-01 to 2023-12-31'],
                ],
                'system_info' => ['incident_date' => '2024-08-22', 'time' => '23:01 - 23:02 (off-hours)', 'affected_system' => 'FictiBank Transaction Database', 'records_deleted' => 847, 'financial_value' => 'RM 4,238,591.20', 'backup_destroyed' => true],
            ],
            default => [],
        };
    }
}
