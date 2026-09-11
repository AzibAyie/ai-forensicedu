<?php
namespace Database\Seeders;

use App\Models\CaseAnswer;
use App\Models\CaseEnrollment;
use App\Models\CaseQuestion;
use App\Models\CaseReport;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Lecturers
        $lecturer1 = User::create([
            'name' => 'Dr. Ahmad Fauzi',
            'email' => 'lecturer@forensicedu.test',
            'password' => Hash::make('password'),
            'role' => 'lecturer',
            'staff_id' => 'L001001',
            'faculty' => 'Faculty of Computing',
            'is_active' => true,
        ]);

        $lecturer2 = User::create([
            'name' => 'Dr. Siti Rahimah',
            'email' => 'lecturer2@forensicedu.test',
            'password' => Hash::make('password'),
            'role' => 'lecturer',
            'staff_id' => 'L001002',
            'faculty' => 'Faculty of Computing',
            'is_active' => true,
        ]);

        // Students
        $students = [];
        $studentData = [
            ['name' => 'Muhammad Amirul', 'email' => 'student@forensicedu.test', 'sid' => 'A21EC0001', 'program' => 'Bachelor of Cybersecurity'],
            ['name' => 'Nurul Izzati', 'email' => 'student2@forensicedu.test', 'sid' => 'A21EC0002', 'program' => 'Bachelor of Cybersecurity'],
            ['name' => 'Haziq Syafiq', 'email' => 'student3@forensicedu.test', 'sid' => 'A21EC0003', 'program' => 'Bachelor of Computer Science'],
            ['name' => 'Farah Liyana', 'email' => 'student4@forensicedu.test', 'sid' => 'A21EC0004', 'program' => 'Bachelor of Cybersecurity'],
            ['name' => 'Danial Arif', 'email' => 'student5@forensicedu.test', 'sid' => 'A21EC0005', 'program' => 'Bachelor of Computer Science'],
        ];

        foreach ($studentData as $s) {
            $students[] = User::create([
                'name' => $s['name'],
                'email' => $s['email'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'student_id' => $s['sid'],
                'faculty' => 'Faculty of Computing',
                'program' => $s['program'],
                'lecturer_id' => $lecturer1->id,
                'is_active' => true,
            ]);
        }

        // ---- CASE 1: Brute Force ----
        $case1 = ForensicCase::create([
            'lecturer_id' => $lecturer1->id,
            'title' => 'Operation Lockpick — HR Portal Breach',
            'incident_type' => 'brute_force',
            'difficulty' => 'beginner',
            'description' => 'A company\'s HR portal was breached following a sustained brute force attack. Payroll data was exfiltrated.',
            'scenario' => 'TechCorp Sdn Bhd\'s IT security team detected unusual activity on the HR portal login system. At approximately 02:14 AM on 15 March 2024, an unauthorized login was successful after 47 failed attempts originating from IP 192.168.1.105. The attacker accessed and downloaded the Q1 2024 payroll file (2.3MB) before logging out 102 seconds later. Your task is to reconstruct the attack timeline and identify the breach.',
            'learning_objectives' => "1. Identify brute force attack patterns from authentication logs.\n2. Trace attacker activity post-compromise.\n3. Determine the scope of data exfiltration.\n4. Reconstruct a timeline from log evidence.\n5. Recommend preventive controls.",
            'investigation_instructions' => "Step 1: Review the audit logs — identify the IP address and username involved.\nStep 2: Count the number of failed login attempts and calculate the time span.\nStep 3: Identify what actions were taken after successful login.\nStep 4: Determine what data was accessed or downloaded.\nStep 5: Build a complete timeline of the incident.\nStep 6: Answer all investigation questions.\nStep 7: Write your forensic report.",
            'simulated_evidence' => [
                'type' => 'brute_force',
                'overview' => 'Authentication logs showing repeated failed logins followed by unauthorized access and data exfiltration.',
                'audit_logs' => [
                    ['timestamp' => '2024-03-15 01:58:12', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid credentials for admin@techcorp.com'],
                    ['timestamp' => '2024-03-15 01:58:13', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid credentials for admin@techcorp.com'],
                    ['timestamp' => '2024-03-15 01:58:25', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid credentials for admin@techcorp.com [attempt 10]'],
                    ['timestamp' => '2024-03-15 02:05:44', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid credentials for admin@techcorp.com [attempt 25]'],
                    ['timestamp' => '2024-03-15 02:12:09', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.105', 'details' => 'Invalid credentials for admin@techcorp.com [attempt 46]'],
                    ['timestamp' => '2024-03-15 02:14:33', 'user' => 'admin', 'action' => 'LOGIN_SUCCESS', 'ip' => '192.168.1.105', 'details' => 'Successful login — attempt 47. Session ID: sess_4f9a2c'],
                    ['timestamp' => '2024-03-15 02:14:45', 'user' => 'admin', 'action' => 'FILE_ACCESSED', 'ip' => '192.168.1.105', 'details' => 'Navigated to /confidential/payroll/'],
                    ['timestamp' => '2024-03-15 02:14:51', 'user' => 'admin', 'action' => 'FILE_ACCESSED', 'ip' => '192.168.1.105', 'details' => 'Opened payroll_Q1_2024.xlsx'],
                    ['timestamp' => '2024-03-15 02:15:01', 'user' => 'admin', 'action' => 'FILE_DOWNLOADED', 'ip' => '192.168.1.105', 'details' => 'Downloaded payroll_Q1_2024.xlsx — Size: 2.3MB'],
                    ['timestamp' => '2024-03-15 02:16:22', 'user' => 'admin', 'action' => 'LOGOUT', 'ip' => '192.168.1.105', 'details' => 'Session terminated. Duration: 109 seconds'],
                ],
                'network_logs' => [
                    ['timestamp' => '2024-03-15 01:58:12', 'source_ip' => '192.168.1.105', 'dest_ip' => '10.0.0.5', 'event' => 'Brute force pattern detected: 1 POST/sec to /login', 'count' => 47],
                    ['timestamp' => '2024-03-15 02:15:01', 'source_ip' => '192.168.1.105', 'dest_ip' => '10.0.0.5', 'event' => 'Large file transfer outbound', 'size' => '2.3MB'],
                ],
                'system_info' => ['incident_date' => '2024-03-15', 'affected_system' => 'TechCorp HR Portal', 'total_failed_attempts' => 47, 'time_to_breach' => '16 minutes 21 seconds', 'session_duration' => '109 seconds', 'data_exfiltrated' => 'payroll_Q1_2024.xlsx (2.3MB)'],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-03-15 01:58', 'event' => 'Automated login attempts begin against the HR portal from 192.168.1.105.'],
                ['timestamp' => '2024-03-15 02:14', 'event' => 'Attempt 47 succeeds. Attacker authenticates as admin.'],
                ['timestamp' => '2024-03-15 02:15', 'event' => 'Q1 payroll spreadsheet downloaded (2.3 MB).'],
                ['timestamp' => '2024-03-15 02:16', 'event' => 'Session terminated. Total dwell time 109 seconds.'],
            ],
            'publish_at' => now()->subDays(7),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 45,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case1->id, 'question' => 'How many failed login attempts were made before the successful breach? What was the time span of the brute force attack?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case1->id, 'question' => 'Identify the attacker\'s IP address and describe the tools or methods likely used based on the log pattern.', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case1->id, 'question' => 'What data was exfiltrated? Describe the timeline from successful login to logout.', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case1->id, 'question' => 'What security controls were missing that allowed this attack to succeed?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case1->id, 'question' => 'Recommend at least 3 specific security controls to prevent this attack from recurring.', 'marks' => 20, 'display_order' => 5]);

        // ---- CASE 2: Unauthorized Modification ----
        $case2 = ForensicCase::create([
            'lecturer_id' => $lecturer1->id,
            'title' => 'The Phantom Raise — Payroll Database Tampering',
            'incident_type' => 'unauthorized_modification',
            'difficulty' => 'intermediate',
            'description' => 'A database administrator modified payroll records for personal gain, then attempted to cover their tracks.',
            'scenario' => 'FictiBank\'s internal audit discovered that employee ID 1042 received an unexpected salary increase of RM33,000/year effective last month. Investigation traces back to database activity at 10:47 PM on 10 June 2024 by user john.smith, a privileged database administrator. The attacker also modified the bank account number and attempted to erase evidence by setting the modified_by field to NULL. Investigate using the audit trail to identify what was changed, when, and how.',
            'learning_objectives' => "1. Identify unauthorized database modifications from audit logs.\n2. Detect evidence tampering and anti-forensics techniques.\n3. Correlate database record changes with timestamps.\n4. Assess the financial impact of insider fraud.\n5. Produce a forensically sound report.",
            'investigation_instructions' => "Step 1: Examine the audit logs for database activity on 2024-06-10.\nStep 2: Identify which database tables and records were modified.\nStep 3: Compare before/after values for each modified field.\nStep 4: Look for evidence tampering (attempts to erase tracks).\nStep 5: Calculate the total financial impact.\nStep 6: Answer all investigation questions.\nStep 7: Write your forensic report.",
            'simulated_evidence' => [
                'type' => 'unauthorized_modification',
                'overview' => 'Database audit trail showing insider modification of payroll records with attempted evidence suppression.',
                'audit_logs' => [
                    ['timestamp' => '2024-06-10 22:47:01', 'user' => 'john.smith', 'action' => 'DB_CONNECT', 'ip' => '10.0.1.22', 'details' => 'Connected to payroll_db as db_admin role'],
                    ['timestamp' => '2024-06-10 22:47:15', 'user' => 'john.smith', 'action' => 'QUERY_SELECT', 'ip' => '10.0.1.22', 'details' => 'SELECT * FROM employees WHERE id = 1042'],
                    ['timestamp' => '2024-06-10 22:47:31', 'user' => 'john.smith', 'action' => 'RECORD_MODIFIED', 'ip' => '10.0.1.22', 'details' => 'UPDATE employees SET salary=85000 WHERE id=1042 — OLD VALUE: 52000'],
                    ['timestamp' => '2024-06-10 22:48:05', 'user' => 'john.smith', 'action' => 'RECORD_MODIFIED', 'ip' => '10.0.1.22', 'details' => 'UPDATE employees SET bank_account=\'MY-NEW-ACCT-9981\' WHERE id=1042 — OLD VALUE: MY-ORIG-ACCT-4421'],
                    ['timestamp' => '2024-06-10 22:48:44', 'user' => 'john.smith', 'action' => 'RECORD_MODIFIED', 'ip' => '10.0.1.22', 'details' => 'UPDATE audit_trail SET modified_by=NULL WHERE record_id=1042 — ANTI-FORENSICS DETECTED'],
                    ['timestamp' => '2024-06-10 22:49:12', 'user' => 'john.smith', 'action' => 'DB_DISCONNECT', 'ip' => '10.0.1.22', 'details' => 'Session closed. Total session duration: 2 minutes 11 seconds'],
                ],
                'database_records' => [
                    ['id' => 1042, 'field' => 'salary', 'table' => 'employees', 'before' => 'RM 52,000/year', 'after' => 'RM 85,000/year', 'changed_at' => '2024-06-10 22:47:31'],
                    ['id' => 1042, 'field' => 'bank_account', 'table' => 'employees', 'before' => 'MY-ORIG-ACCT-4421', 'after' => 'MY-NEW-ACCT-9981', 'changed_at' => '2024-06-10 22:48:05'],
                    ['id' => 1042, 'field' => 'modified_by', 'table' => 'audit_trail', 'before' => 'john.smith', 'after' => 'NULL', 'changed_at' => '2024-06-10 22:48:44'],
                ],
                'system_info' => ['incident_date' => '2024-06-10', 'incident_time' => '22:47 - 22:49', 'affected_system' => 'FictiBank HR Payroll Database', 'perpetrator_account' => 'john.smith (db_admin)', 'affected_employee_id' => 1042, 'financial_impact' => 'RM 33,000/year salary inflation + bank account redirection'],
                'suspects' => [
                    ['name' => 'John Smith', 'role' => 'Database Administrator (john.smith)', 'culprit' => true],
                    ['name' => 'Ahmad Zulkifli', 'role' => 'Employee #1042 — received the raise', 'culprit' => false],
                    ['name' => 'Sarah Chen', 'role' => 'HR Manager', 'culprit' => false],
                    ['name' => 'Mike Wong', 'role' => 'Junior DBA, shares an office with John', 'culprit' => false],
                ],
            ],
            'publish_at' => now()->subDays(3),
            'close_at' => now()->addDays(14),
            'password' => 'forensic2024',
            'is_locked' => true,
            'is_published' => true,
            'expected_duration' => 60,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case2->id, 'question' => 'List all database fields that were modified. For each field, state the original value and the new value.', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case2->id, 'question' => 'Identify the anti-forensics technique used by the attacker. What was the purpose of this action?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case2->id, 'question' => 'Calculate the total potential financial loss if this modification went undetected for 12 months.', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case2->id, 'question' => 'What database security controls should be implemented to prevent privileged insiders from performing unauthorized modifications?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case2->id, 'question' => "What role did the compromised account's excessive privileges play in enabling this incident, and how should access be scoped differently?", 'marks' => 20, 'display_order' => 5]);

        // ---- CASE 3: Mass Deletion ----
        $case3 = ForensicCase::create([
            'lecturer_id' => $lecturer2->id,
            'title' => 'Operation Blackout — Mass Transaction Record Deletion',
            'incident_type' => 'mass_deletion',
            'difficulty' => 'advanced',
            'description' => 'A database administrator deleted 847 customer transaction records worth RM 4.2M during off-hours, also destroying backup copies.',
            'scenario' => 'FictiBank\'s compliance team discovered that 847 customer transaction records dated throughout 2023 have been permanently deleted from the system. The deletion occurred on 22 August 2024 between 23:01 and 23:02, outside business hours. Alarmingly, the attacker also deleted the backup copies of the same records. The total value of deleted transactions is estimated at RM 4,238,591.20. Investigate using the system audit logs to identify the perpetrator, method, and full scope of the incident.',
            'learning_objectives' => "1. Identify bulk data deletion events from system logs.\n2. Assess the scope and financial impact of data destruction.\n3. Detect deliberate destruction of backup evidence.\n4. Reconstruct the attack from minimal log evidence.\n5. Recommend disaster recovery and data protection strategies.",
            'investigation_instructions' => "Step 1: Examine the audit logs for database activity on 2024-08-22 after 23:00.\nStep 2: Identify the user account, IP address, and exact queries executed.\nStep 3: Determine the number of records deleted and the tables affected.\nStep 4: Identify whether backup records were also destroyed.\nStep 5: Calculate the financial impact.\nStep 6: Assess whether this was accidental or deliberate.\nStep 7: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'mass_deletion',
                'overview' => 'System audit logs capturing bulk deletion of transaction records and backup destruction during off-hours.',
                'audit_logs' => [
                    ['timestamp' => '2024-08-22 23:01:15', 'user' => 'sys_admin', 'action' => 'DB_CONNECT', 'ip' => '172.16.0.8', 'details' => 'Connected to transactions_db — outside business hours'],
                    ['timestamp' => '2024-08-22 23:01:29', 'user' => 'sys_admin', 'action' => 'QUERY_EXECUTED', 'ip' => '172.16.0.8', 'details' => 'SELECT COUNT(*) FROM transactions WHERE YEAR(date)=2023 — Result: 847'],
                    ['timestamp' => '2024-08-22 23:01:33', 'user' => 'sys_admin', 'action' => 'QUERY_EXECUTED', 'ip' => '172.16.0.8', 'details' => 'DELETE FROM transactions WHERE YEAR(date)=2023'],
                    ['timestamp' => '2024-08-22 23:01:34', 'user' => 'sys_admin', 'action' => 'BULK_DELETE', 'ip' => '172.16.0.8', 'details' => '847 records deleted in 1.2 seconds — Total value: RM 4,238,591.20'],
                    ['timestamp' => '2024-08-22 23:01:38', 'user' => 'sys_admin', 'action' => 'QUERY_EXECUTED', 'ip' => '172.16.0.8', 'details' => 'DELETE FROM transaction_backups WHERE YEAR(backup_date)=2023'],
                    ['timestamp' => '2024-08-22 23:01:42', 'user' => 'sys_admin', 'action' => 'BULK_DELETE', 'ip' => '172.16.0.8', 'details' => '847 backup records deleted — BACKUP DESTRUCTION DETECTED'],
                    ['timestamp' => '2024-08-22 23:02:01', 'user' => 'sys_admin', 'action' => 'DB_DISCONNECT', 'ip' => '172.16.0.8', 'details' => 'Session terminated. Total duration: 46 seconds'],
                ],
                'database_records' => [
                    ['summary' => 'Primary records deleted', 'table' => 'transactions', 'count' => 847, 'date_range' => '2023-01-01 to 2023-12-31', 'total_value' => 'RM 4,238,591.20', 'method' => 'Bulk DELETE WHERE YEAR(date)=2023'],
                    ['summary' => 'Backup records destroyed', 'table' => 'transaction_backups', 'count' => 847, 'date_range' => '2023-01-01 to 2023-12-31', 'total_value' => 'RM 4,238,591.20', 'method' => 'Bulk DELETE WHERE YEAR(backup_date)=2023'],
                ],
                'system_info' => ['incident_date' => '2024-08-22', 'incident_time' => '23:01:15 - 23:02:01 (46 seconds)', 'affected_system' => 'FictiBank Transaction Database', 'perpetrator_account' => 'sys_admin', 'attacker_ip' => '172.16.0.8', 'records_deleted' => 847, 'financial_value_destroyed' => 'RM 4,238,591.20', 'backups_destroyed' => true, 'occurrence' => 'Off-hours (after 11 PM)'],
                'suspects' => [
                    ['name' => 'On-duty sys_admin', 'role' => 'Systems Administrator (172.16.0.8)', 'culprit' => true],
                    ['name' => 'Faizal Rahman', 'role' => 'Network Engineer', 'culprit' => false],
                    ['name' => 'Priya Nair', 'role' => 'Compliance Officer — flagged the deletion', 'culprit' => false],
                    ['name' => 'Outsourced IT Vendor', 'role' => 'Third-party support contract', 'culprit' => false],
                ],
            ],
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 90,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case3->id, 'question' => 'Identify the user account that performed the deletion. What is the exact SQL query used and how many records were affected?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case3->id, 'question' => 'Was the deletion of primary records AND backup records coincidental? What does this indicate about the attacker\'s intent?', 'marks' => 25, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case3->id, 'question' => 'Calculate the total financial value of destroyed records. What is the potential regulatory and legal impact?', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case3->id, 'question' => 'The incident took only 46 seconds. What does this suggest about pre-planning? What evidence supports or contradicts premeditation?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case3->id, 'question' => 'Recommend a comprehensive data protection strategy including backup policies, access controls, and monitoring to prevent this.', 'marks' => 15, 'display_order' => 5]);


        // ---- DEMO GRADEBOOK DATA ----
        // Gives the lecturer gradebook real rows on first run. Model answers
        // per case so seeded reports show real per-question work instead of
        // just the findings narrative — a case with 0 saved answers reads as
        // "the student skipped the investigation" on the grading screen.
        $modelAnswers = [
            $case1->id => [
                "47 failed login attempts were recorded between 01:58:12 and 02:14:33 on 15 March 2024, a span of roughly 16 minutes 21 seconds, before the successful login on attempt 47.",
                "The attacker's IP address was 192.168.1.105. The steady one-attempt-per-second pattern with no delay between failures is consistent with an automated brute-force script rather than manual guessing.",
                "The Q1 2024 payroll spreadsheet (payroll_Q1_2024.xlsx, 2.3MB) was downloaded. The attacker logged in at 02:14:33, browsed to /confidential/payroll/ at 02:14:45, opened the file at 02:14:51, downloaded it at 02:15:01, and logged out at 02:16:22 — a total session of 109 seconds.",
                "There was no account lockout policy after repeated failed attempts, no rate limiting on the login endpoint, and no multi-factor authentication on the admin account, which allowed 47 consecutive guesses without any block or alert.",
                "1) Enforce an account lockout after 5 failed attempts. 2) Require multi-factor authentication for all admin accounts. 3) Add rate limiting / CAPTCHA on the login endpoint. 4) Alert on off-hours access to payroll data.",
            ],
            $case2->id => [
                "Two employee record fields were modified for ID 1042: salary changed from RM52,000/year to RM85,000/year, and bank_account changed from MY-ORIG-ACCT-4421 to MY-NEW-ACCT-9981.",
                "The attacker set the audit_trail.modified_by field to NULL for record 1042 after making the changes. This is an anti-forensics technique intended to erase the trail of who made the modification.",
                "The salary inflation alone (RM33,000/year) would total RM33,000 over 12 months if undetected, plus any funds redirected to the substituted bank account before the change was caught.",
                "Separation of duties so no single DBA account can both modify payroll records and alter the audit trail; write-once/append-only audit logging; mandatory second-approval for salary or bank account changes above a threshold.",
                "The db_admin role had unrestricted write access to both the employees table and the audit_trail table. Access should be scoped so payroll changes require a distinct, logged approval workflow.",
            ],
            $case3->id => [
                "The sys_admin account executed 'DELETE FROM transactions WHERE YEAR(date)=2023', deleting 847 records at 23:01:34 from IP 172.16.0.8.",
                "No — this was deliberate. The attacker deleted the primary records, then four seconds later deleted the matching backup records for the same date range, indicating intent to make recovery impossible.",
                "The destroyed records were worth RM4,238,591.20, which could also trigger regulatory reporting obligations for failing to protect customer transaction records.",
                "Completing both deletions in 46 seconds with no exploratory queries beyond a single COUNT check suggests the attacker already knew the exact tables, date range, and backup location — consistent with premeditation.",
                "Maintain backups that cannot be deleted by the same credentials used for production access, enforce approval workflows for bulk deletes, restrict off-hours database access, and alert on large DELETE operations.",
            ],
        ];

        $demo = [
            // [student index, case, status, marks|null]
            [1, $case1, 'graded',    82],
            [1, $case3, 'submitted', null],
            [2, $case1, 'graded',    64],
            [2, $case2, 'graded',    91],
            [3, $case1, 'graded',    47],
            [3, $case2, 'submitted', null],
            [4, $case1, 'in_progress', null],
        ];

        foreach ($demo as [$idx, $case, $status, $marks]) {
            $student = $students[$idx];

            $enrollment = CaseEnrollment::create([
                'forensic_case_id' => $case->id,
                'student_id' => $student->id,
                'status' => $status,
                'progress_percent' => $status === 'in_progress' ? 45 : 100,
                'started_at' => now()->subDays(rand(6, 20)),
                'submitted_at' => $status === 'in_progress' ? null : now()->subDays(rand(1, 5)),
            ]);

            if ($status === 'in_progress') {
                continue;
            }

            foreach ($case->questions()->orderBy('display_order')->get() as $i => $question) {
                CaseAnswer::create([
                    'enrollment_id' => $enrollment->id,
                    'question_id' => $question->id,
                    'answer' => $modelAnswers[$case->id][$i] ?? 'Answer on file.',
                ]);
            }

            CaseReport::create([
                'enrollment_id' => $enrollment->id,
                'student_name' => $student->name,
                'student_id_number' => $student->student_id,
                'program' => $student->program,
                'executive_summary' => 'Sample submission generated by the seeder for demonstration of the gradebook. Replace with real student work.',
                'findings' => 'Analysis of the audit logs identified the originating IP address and the sequence of actions taken by the attacker following authentication.',
                'timeline_reconstruction' => 'Events were reconstructed from timestamped log entries spanning the intrusion window.',
                'recommendations' => 'Enforce account lockout thresholds, enable multi-factor authentication, and alert on off-hours privileged access.',
                'conclusion' => 'The incident is assessed as a deliberate intrusion rather than an accidental access.',
                'marks' => $marks,
                'lecturer_feedback' => $marks !== null ? 'Solid reconstruction of the timeline. Strengthen the IOC analysis and be more specific in your recommendations.' : null,
                'status' => $marks !== null ? 'graded' : 'submitted',
                // Varied composition telemetry so the authorship panel demonstrates
                // both a normal profile and one worth a second look.
                'keystroke_count' => $idx === 3 ? 180 : rand(1400, 3200),
                'paste_count' => $idx === 3 ? 3 : rand(0, 2),
                'pasted_chars' => $idx === 3 ? 1180 : rand(0, 140),
                'compose_seconds' => $idx === 3 ? 190 : rand(900, 2600),
                'revision_count' => $idx === 3 ? 1 : rand(3, 9),
                'paste_events' => $idx === 3 ? [
                    ['field' => 'findings', 'length' => 610, 'at' => now()->subDays(2)->toIso8601String()],
                    ['field' => 'recommendations', 'length' => 340, 'at' => now()->subDays(2)->toIso8601String()],
                    ['field' => 'conclusion', 'length' => 230, 'at' => now()->subDays(2)->toIso8601String()],
                ] : null,
            ]);
        }

        $this->call(DemoCasesSeeder::class);
        $this->call(ExtraCaseSeeder::class);
        $this->call(Lecturer1CasesSeeder::class);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('Lecturer: lecturer@forensicedu.test / password');
        $this->command->info('Student: student@forensicedu.test / password');
        $this->command->info('Case 2 password: forensic2024');
        $this->command->info('All students assigned to lecturer@forensicedu.test');
    }
}
