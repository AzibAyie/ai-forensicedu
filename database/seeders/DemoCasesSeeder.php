<?php
namespace Database\Seeders;

use App\Models\CaseEnrollment;
use App\Models\CaseQuestion;
use App\Models\CaseReport;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Two additional demo scenarios, on top of the three in DatabaseSeeder,
 * so a fresh install (or the live demo DB) has five cases to show off the
 * platform's range. Looks lecturers/students up by their seeded emails
 * instead of creating them, so this is safe to run against a database
 * that already has real activity in it.
 */
class DemoCasesSeeder extends Seeder
{
    public function run(): void
    {
        $lecturer1 = User::where('email', 'lecturer@forensicedu.test')->first();
        $lecturer2 = User::where('email', 'lecturer2@forensicedu.test')->first();

        if (! $lecturer1 || ! $lecturer2) {
            $this->command->error('Expected lecturers not found — run the main DatabaseSeeder first.');
            return;
        }

        if (ForensicCase::where('title', 'Operation Gradewatch — Examination Portal Breach')->exists()) {
            $this->command->warn('Demo cases already exist — skipping.');
            return;
        }

        $students = User::where('role', 'student')->orderBy('student_id')->get();

        // ---- CASE 4: Brute Force (exam portal) ----
        $case4 = ForensicCase::create([
            'lecturer_id' => $lecturer1->id,
            'title' => 'Operation Gradewatch — Examination Portal Breach',
            'incident_type' => 'brute_force',
            'difficulty' => 'intermediate',
            'description' => 'A brute force attack against the university exam portal led to unauthorized access and download of a confidential exam paper before its scheduled release.',
            'scenario' => "UPTM's Online Examination Portal (OEP) logged unusual authentication activity against a lecturer account, dr.tan, three days before the Database Systems final exam was due to go live. Between 03:12 AM and 03:26 AM on 2 December 2024, 63 failed login attempts were recorded from IP 203.0.113.44 before a successful login. The attacker then browsed to the exam bank, downloaded \"DBS301_Final_v2.pdf\", and opened the answer key before disconnecting. IT flagged the account the next morning after dr.tan reported never logging in at that hour. Investigate the logs to determine what happened and how far the exposure went.",
            'learning_objectives' => "1. Identify brute force attack patterns from authentication logs.\n2. Trace attacker activity following a successful compromise.\n3. Determine the scope of confidential material exposed.\n4. Reconstruct a precise timeline from timestamped log evidence.\n5. Recommend controls to protect examination integrity.",
            'investigation_instructions' => "Step 1: Review the audit logs — identify the account targeted and the source IP.\nStep 2: Count the failed login attempts and calculate the time span of the attack.\nStep 3: Identify every file the attacker accessed or downloaded after logging in.\nStep 4: Determine whether the answer key, not just the question paper, was exposed.\nStep 5: Build a complete timeline of the incident.\nStep 6: Answer all investigation questions.\nStep 7: Write your forensic report.",
            'simulated_evidence' => [
                'type' => 'brute_force',
                'overview' => 'Authentication logs showing a sustained brute force attempt against a lecturer account, followed by unauthorized access to exam materials.',
                'audit_logs' => [
                    ['timestamp' => '2024-12-02 03:12:04', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '203.0.113.44', 'details' => 'Invalid credentials for dr.tan@uptm.edu.my'],
                    ['timestamp' => '2024-12-02 03:12:19', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '203.0.113.44', 'details' => 'Invalid credentials for dr.tan@uptm.edu.my [attempt 8]'],
                    ['timestamp' => '2024-12-02 03:17:52', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '203.0.113.44', 'details' => 'Invalid credentials for dr.tan@uptm.edu.my [attempt 30]'],
                    ['timestamp' => '2024-12-02 03:23:41', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '203.0.113.44', 'details' => 'Invalid credentials for dr.tan@uptm.edu.my [attempt 55]'],
                    ['timestamp' => '2024-12-02 03:26:08', 'user' => 'dr.tan', 'action' => 'LOGIN_SUCCESS', 'ip' => '203.0.113.44', 'details' => 'Successful login — attempt 63. Session ID: sess_ge7k91'],
                    ['timestamp' => '2024-12-02 03:26:20', 'user' => 'dr.tan', 'action' => 'FILE_ACCESSED', 'ip' => '203.0.113.44', 'details' => 'Navigated to /exam-bank/DBS301/'],
                    ['timestamp' => '2024-12-02 03:26:37', 'user' => 'dr.tan', 'action' => 'FILE_DOWNLOADED', 'ip' => '203.0.113.44', 'details' => 'Downloaded DBS301_Final_v2.pdf — Size: 1.1MB'],
                    ['timestamp' => '2024-12-02 03:27:02', 'user' => 'dr.tan', 'action' => 'FILE_ACCESSED', 'ip' => '203.0.113.44', 'details' => 'Opened DBS301_AnswerKey.xlsx'],
                    ['timestamp' => '2024-12-02 03:28:15', 'user' => 'dr.tan', 'action' => 'LOGOUT', 'ip' => '203.0.113.44', 'details' => 'Session terminated. Duration: 2 minutes 7 seconds'],
                ],
                'network_logs' => [
                    ['timestamp' => '2024-12-02 03:12:04', 'source_ip' => '203.0.113.44', 'event' => 'Brute force pattern detected: ~1 login attempt every 13 seconds', 'count' => 63],
                    ['timestamp' => '2024-12-02 03:26:37', 'source_ip' => '203.0.113.44', 'event' => 'Outbound file transfer', 'size' => '1.1MB'],
                ],
                'system_info' => ['incident_date' => '2024-12-02', 'affected_system' => 'UPTM Online Examination Portal', 'total_failed_attempts' => 63, 'time_to_breach' => '14 minutes 4 seconds', 'session_duration' => '2 minutes 7 seconds', 'data_exposed' => 'DBS301_Final_v2.pdf and DBS301_AnswerKey.xlsx'],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-12-02 03:12', 'event' => 'Automated login attempts begin against dr.tan\'s account from 203.0.113.44.'],
                ['timestamp' => '2024-12-02 03:26', 'event' => 'Attempt 63 succeeds. Attacker authenticates as dr.tan.'],
                ['timestamp' => '2024-12-02 03:26', 'event' => 'Final exam question paper downloaded from the exam bank.'],
                ['timestamp' => '2024-12-02 03:27', 'event' => 'Answer key file opened.'],
                ['timestamp' => '2024-12-02 03:28', 'event' => 'Session terminated. Total dwell time 2 minutes 7 seconds.'],
            ],
            'publish_at' => now()->subDays(2),
            'close_at' => now()->addDays(18),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 60,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case4->id, 'question' => 'How many failed login attempts were made before the successful breach, and over what time span did they occur?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case4->id, 'question' => 'Identify the attacker\'s IP address and describe the login pattern that reveals this was an automated attack rather than a human guessing manually.', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case4->id, 'question' => 'List every file the attacker accessed after logging in. Was only the question paper exposed, or was more at risk?', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case4->id, 'question' => 'Given the exam was still three days away, what should the university do about the exam paper itself, not just the account?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case4->id, 'question' => 'Recommend at least 3 specific controls that would have prevented or limited this breach.', 'marks' => 20, 'display_order' => 5]);

        // ---- CASE 5: Unauthorized Modification (grade tampering) ----
        $case5 = ForensicCase::create([
            'lecturer_id' => $lecturer2->id,
            'title' => 'Grade Ghost — Academic Record Tampering',
            'incident_type' => 'unauthorized_modification',
            'difficulty' => 'beginner',
            'description' => 'A failing final grade was silently changed to a passing grade in the student records system after a lecturer left a session unattended.',
            'scenario' => "During semester-end grade verification, the Faculty of Computing's registrar found a discrepancy: student A20BC1234's final grade for Database Systems was showing as \"B+\" in the transcript draft, but the lecturer's original mark sheet clearly recorded an \"F\". Audit logs show that on 18 December 2024, a lecturer's session on a shared computer in Lab 3 was left open and unattended. At 14:32, while that session was still active, the grade field for A20BC1234 was updated directly in the student_records table — bypassing the normal grade-submission workflow entirely. Investigate the audit trail to determine exactly what happened and who was logged in at the time.",
            'learning_objectives' => "1. Identify unauthorized record modifications from a database audit trail.\n2. Distinguish a normal workflow action from a direct, out-of-process edit.\n3. Correlate a suspicious change with session and access context.\n4. Assess the integrity impact of academic record tampering.\n5. Recommend controls that reduce the risk of unattended-session abuse.",
            'investigation_instructions' => "Step 1: Examine the audit logs for activity on the student_records table on 2024-12-18.\nStep 2: Identify which record was modified, and compare the value before and after.\nStep 3: Determine whether the change went through the normal grade-submission workflow or bypassed it.\nStep 4: Identify what session/account the change was made under, and what that suggests about how it happened.\nStep 5: Answer all investigation questions.\nStep 6: Write your forensic report.",
            'simulated_evidence' => [
                'type' => 'unauthorized_modification',
                'overview' => 'Database audit trail showing a direct, out-of-workflow grade change made during an unattended lecturer session.',
                'audit_logs' => [
                    ['timestamp' => '2024-12-18 09:03:11', 'user' => 'dr.lim', 'action' => 'LOGIN_SUCCESS', 'ip' => '10.20.4.15', 'details' => 'Logged in at Lab 3, Workstation 7'],
                    ['timestamp' => '2024-12-18 09:15:40', 'user' => 'dr.lim', 'action' => 'GRADE_SUBMITTED', 'ip' => '10.20.4.15', 'details' => 'Submitted final grades for DBS301 via grade-submission workflow — batch of 42 students'],
                    ['timestamp' => '2024-12-18 12:00:00', 'user' => 'dr.lim', 'action' => 'SESSION_IDLE', 'ip' => '10.20.4.15', 'details' => 'No activity for 2+ hours — session remained authenticated'],
                    ['timestamp' => '2024-12-18 14:32:07', 'user' => 'dr.lim', 'action' => 'RECORD_MODIFIED', 'ip' => '10.20.4.15', 'details' => 'UPDATE student_records SET grade=\'B+\' WHERE student_id=\'A20BC1234\' AND course=\'DBS301\' — OLD VALUE: F — bypassed grade-submission workflow'],
                    ['timestamp' => '2024-12-18 14:33:02', 'user' => 'dr.lim', 'action' => 'LOGOUT', 'ip' => '10.20.4.15', 'details' => 'Session closed'],
                    ['timestamp' => '2024-12-18 16:47:19', 'user' => 'dr.lim', 'action' => 'LOGIN_SUCCESS', 'ip' => '192.168.4.2', 'details' => 'Logged in from Faculty office — different device'],
                ],
                'database_records' => [
                    ['id' => 'A20BC1234', 'field' => 'grade', 'table' => 'student_records', 'before' => 'F', 'after' => 'B+', 'changed_at' => '2024-12-18 14:32:07'],
                ],
                'system_info' => ['incident_date' => '2024-12-18', 'affected_system' => 'Faculty of Computing Student Records System', 'affected_student' => 'A20BC1234', 'course' => 'Database Systems (DBS301)', 'change_method' => 'Direct table UPDATE, not the grade-submission workflow', 'session_context' => 'Change occurred after 2+ hours of idle time on an unattended, still-authenticated session'],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-12-18 09:03', 'event' => 'dr.lim logs in at Lab 3, Workstation 7.'],
                ['timestamp' => '2024-12-18 09:15', 'event' => 'Final grades for all 42 DBS301 students submitted normally through the grade workflow, including an F for A20BC1234.'],
                ['timestamp' => '2024-12-18 12:00', 'event' => 'Session goes idle for over two hours but remains authenticated.'],
                ['timestamp' => '2024-12-18 14:32', 'event' => 'A20BC1234\'s grade is changed directly in the database from F to B+, outside the normal workflow.'],
                ['timestamp' => '2024-12-18 14:33', 'event' => 'Session logged out.'],
            ],
            'publish_at' => now()->subDay(),
            'close_at' => now()->addDays(20),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 45,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case5->id, 'question' => 'What was the original grade, what was it changed to, and exactly when did the change occur?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case5->id, 'question' => 'How does the log entry for the 14:32 change differ from the normal grade-submission entry at 09:15? What does that difference tell you?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case5->id, 'question' => 'The change happened under dr.lim\'s account, but does the evidence prove dr.lim made it personally? Explain your reasoning using the session and idle-time evidence.', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case5->id, 'question' => 'Recommend at least 3 controls (technical or procedural) that would prevent this kind of unattended-session abuse.', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case5->id, 'question' => 'If this had gone unnoticed until the transcript was finalised, what would the real-world impact have been for the institution and other students?', 'marks' => 20, 'display_order' => 5]);

        // ---- Light demo enrollment data so both new cases show up in gradebooks too ----
        if ($students->count() >= 5) {
            $modelAnswers = [
                $case4->id => [
                    "63 failed login attempts were recorded against dr.tan's account between 03:12:04 and 03:26:08 on 2 December 2024 — a span of roughly 14 minutes.",
                    "The source IP was 203.0.113.44. Attempts arrived roughly every 13 seconds with no variation, consistent with an automated brute-force tool rather than a person manually retrying a password.",
                    "The attacker downloaded DBS301_Final_v2.pdf and also opened DBS301_AnswerKey.xlsx — so both the exam questions and the answer key were exposed, not just the question paper.",
                    "Since the exam was still three days away, the university should treat the paper as compromised and replace it with a new version before the scheduled sitting.",
                    "1) Enforce account lockout after repeated failed logins. 2) Require MFA on staff accounts with exam bank access. 3) Rate-limit the login endpoint. 4) Restrict exam bank access to specific IP ranges or hours.",
                ],
                $case5->id => [
                    "Student A20BC1234's Database Systems grade was originally submitted as an F at 09:15 via the normal grade-submission workflow, then changed directly to B+ at 14:32 the same day.",
                    "The 09:15 entry was logged as a GRADE_SUBMITTED action through the standard batch workflow. The 14:32 entry was a RECORD_MODIFIED action — a direct UPDATE bypassing the grade-submission process entirely.",
                    "Not conclusively — the session had been idle for over two hours before the change occurred, and dr.lim's account was still authenticated on an unattended lab workstation.",
                    "1) Automatically time out idle sessions. 2) Require a second factor for direct record edits outside the normal workflow. 3) Restrict direct table edits to a smaller, more tightly audited group.",
                    "Had this gone unnoticed until the transcript was finalised, the institution would have issued an inaccurate academic record, and other tampered records from the same window might have gone unchecked too.",
                ],
            ];

            $demo = [
                [0, $case4, 'graded', 74],
                [2, $case4, 'submitted', null],
                [3, $case5, 'graded', 88],
                [4, $case5, 'in_progress', null],
            ];

            foreach ($demo as [$idx, $case, $status, $marks]) {
                $student = $students[$idx];

                $enrollment = CaseEnrollment::create([
                    'forensic_case_id' => $case->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'progress_percent' => $status === 'in_progress' ? 55 : 100,
                    'started_at' => now()->subDays(rand(2, 10)),
                    'submitted_at' => $status === 'in_progress' ? null : now()->subDays(rand(1, 3)),
                ]);

                $questions = $case->questions()->orderBy('display_order')->get();
                $answersForCase = $modelAnswers[$case->id] ?? [];
                $answerLimit = $status === 'in_progress' ? 2 : $questions->count();

                foreach ($questions->take($answerLimit) as $i => $question) {
                    CaseAnswer::create([
                        'enrollment_id' => $enrollment->id,
                        'question_id' => $question->id,
                        'answer' => $answersForCase[$i] ?? 'Answer on file.',
                    ]);
                }

                if ($status === 'in_progress') {
                    continue;
                }

                CaseReport::create([
                    'enrollment_id' => $enrollment->id,
                    'student_name' => $student->name,
                    'student_id_number' => $student->student_id,
                    'program' => $student->program,
                    'findings' => 'Analysis of the audit logs identified the account and time window involved, and traced the sequence of actions that followed the initial compromise.',
                    'marks' => $marks,
                    'lecturer_feedback' => $marks !== null ? 'Good use of the timestamps to build the timeline. Tighten up the recommendations section next time.' : null,
                    'status' => $marks !== null ? 'graded' : 'submitted',
                    'keystroke_count' => rand(1200, 2800),
                    'paste_count' => rand(0, 1),
                    'pasted_chars' => rand(0, 80),
                    'compose_seconds' => rand(800, 2200),
                    'revision_count' => rand(2, 7),
                ]);
            }
        }

        $this->command->info('Demo cases seeded: Operation Gradewatch, Grade Ghost.');
    }
}
