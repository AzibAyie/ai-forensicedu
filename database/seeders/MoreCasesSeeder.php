<?php
namespace Database\Seeders;

use App\Models\CaseAnswer;
use App\Models\CaseEnrollment;
use App\Models\CaseQuestion;
use App\Models\CaseReport;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Three more full case scenarios, bringing the platform to 11 total.
 * Two get a "suspects" list (the Name the Suspect accusation feature) since
 * they have a clear insider narrative; the credential-stuffing case is left
 * without one, same as the other two external-attacker brute_force cases,
 * since there's no named insider to accuse.
 */
class MoreCasesSeeder extends Seeder
{
    public function run(): void
    {
        $lecturer1 = User::where('email', 'lecturer@forensicedu.test')->first();
        $lecturer2 = User::where('email', 'lecturer2@forensicedu.test')->first();

        if (! $lecturer1 || ! $lecturer2) {
            $this->command->error('Expected lecturers not found — run the main DatabaseSeeder first.');
            return;
        }

        if (ForensicCase::where('title', 'Ghost Refund — Customer Service Fraud')->exists()) {
            $this->command->warn('This batch of cases already exists — skipping.');
            return;
        }

        $students = User::where('role', 'student')->orderBy('student_id')->get();

        // ---- CASE 9: Unauthorized Modification (fraudulent refunds) ----
        $caseA = ForensicCase::create([
            'lecturer_id' => $lecturer2->id,
            'title' => 'Ghost Refund — Customer Service Fraud',
            'incident_type' => 'unauthorized_modification',
            'difficulty' => 'intermediate',
            'description' => 'A customer service representative issued six refunds for unrelated orders, all routed to the same personal e-wallet.',
            'scenario' => "BrightBasket Online's finance team was reconciling refund payouts when an automated report surfaced something odd: the e-wallet ID W-8842 had received six separate customer refunds over a two-week period, each logged under a different order number and a different customer account. All six refunds were processed by the same customer service representative, s.devi, each justified with a generic complaint reason (\"item damaged\" or \"item never arrived\") rather than a documented return. No returned items were ever logged against any of the six orders. The total value redirected to that single wallet is RM14,750.00. Investigate the refund logs to establish exactly what happened and who benefited.",
            'learning_objectives' => "1. Identify a pattern of fraud spread across seemingly unrelated transactions.\n2. Recognise when a documented business reason (a refund justification) is being used to disguise misconduct.\n3. Correlate a common destination account across multiple records to find the true beneficiary.\n4. Assess financial impact from transactional evidence.\n5. Recommend controls for refund-handling roles specifically.",
            'investigation_instructions' => "Step 1: Review the refund audit logs for the two-week period.\nStep 2: Identify every refund routed to wallet W-8842 — the order number, customer, reason given, and amount.\nStep 3: Check whether any of those orders have a matching logged return of physical goods.\nStep 4: Identify which employee account processed all six refunds.\nStep 5: Calculate the total amount redirected.\nStep 6: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'unauthorized_modification',
                'overview' => 'Refund processing logs showing six unrelated customer orders refunded to the same personal e-wallet by the same employee.',
                'audit_logs' => [
                    ['timestamp' => '2024-09-02 11:14:02', 'user' => 's.devi', 'action' => 'LOGIN_SUCCESS', 'ip' => '10.4.12.7', 'details' => 'Logged in to customer service console'],
                    ['timestamp' => '2024-09-02 11:16:40', 'user' => 's.devi', 'action' => 'REFUND_ISSUED', 'ip' => '10.4.12.7', 'details' => "Order #ZB-10234 — reason: 'item damaged - customer complaint' — amount RM2,450.00 — destination: wallet W-8842"],
                    ['timestamp' => '2024-09-02 11:22:15', 'user' => 's.devi', 'action' => 'REFUND_ISSUED', 'ip' => '10.4.12.7', 'details' => "Order #ZB-10251 — reason: 'item damaged - customer complaint' — amount RM1,980.00 — destination: wallet W-8842"],
                    ['timestamp' => '2024-09-05 14:03:51', 'user' => 's.devi', 'action' => 'REFUND_ISSUED', 'ip' => '10.4.12.7', 'details' => "Order #ZB-10390 — reason: 'item never arrived' — amount RM3,120.00 — destination: wallet W-8842"],
                    ['timestamp' => '2024-09-09 09:47:22', 'user' => 's.devi', 'action' => 'REFUND_ISSUED', 'ip' => '10.4.12.7', 'details' => "Order #ZB-10502 — reason: 'item damaged - customer complaint' — amount RM2,700.00 — destination: wallet W-8842"],
                    ['timestamp' => '2024-09-12 16:11:09', 'user' => 's.devi', 'action' => 'REFUND_ISSUED', 'ip' => '10.4.12.7', 'details' => "Order #ZB-10618 — reason: 'item never arrived' — amount RM1,850.00 — destination: wallet W-8842"],
                    ['timestamp' => '2024-09-15 10:29:44', 'user' => 's.devi', 'action' => 'REFUND_ISSUED', 'ip' => '10.4.12.7', 'details' => "Order #ZB-10777 — reason: 'item damaged - customer complaint' — amount RM2,650.00 — destination: wallet W-8842"],
                    ['timestamp' => '2024-09-16 08:05:00', 'user' => 'finance_audit', 'action' => 'QUERY_EXECUTED', 'ip' => '10.4.2.1', 'details' => "SELECT * FROM refunds WHERE destination_wallet='W-8842' — Result: 6 refunds matched"],
                    ['timestamp' => '2024-09-16 08:20:12', 'user' => 'finance_audit', 'action' => 'FLAGGED', 'ip' => '10.4.2.1', 'details' => 'Same destination wallet appears across 6 unrelated customer refunds — escalated to fraud review'],
                ],
                'database_records' => [
                    ['order_id' => 'ZB-10234', 'customer' => 'Customer #6612', 'reason' => 'item damaged', 'destination_wallet' => 'W-8842', 'amount' => 'RM 2,450.00', 'returned_item_logged' => 'No'],
                    ['order_id' => 'ZB-10251', 'customer' => 'Customer #7734', 'reason' => 'item damaged', 'destination_wallet' => 'W-8842', 'amount' => 'RM 1,980.00', 'returned_item_logged' => 'No'],
                    ['order_id' => 'ZB-10390', 'customer' => 'Customer #4402', 'reason' => 'item never arrived', 'destination_wallet' => 'W-8842', 'amount' => 'RM 3,120.00', 'returned_item_logged' => 'No'],
                    ['order_id' => 'ZB-10502', 'customer' => 'Customer #9911', 'reason' => 'item damaged', 'destination_wallet' => 'W-8842', 'amount' => 'RM 2,700.00', 'returned_item_logged' => 'No'],
                    ['order_id' => 'ZB-10618', 'customer' => 'Customer #2287', 'reason' => 'item never arrived', 'destination_wallet' => 'W-8842', 'amount' => 'RM 1,850.00', 'returned_item_logged' => 'No'],
                    ['order_id' => 'ZB-10777', 'customer' => 'Customer #5530', 'reason' => 'item damaged', 'destination_wallet' => 'W-8842', 'amount' => 'RM 2,650.00', 'returned_item_logged' => 'No'],
                ],
                'system_info' => [
                    'incident_period' => '2024-09-02 to 2024-09-15 (2 weeks)',
                    'affected_system' => 'BrightBasket Online refund processing console',
                    'perpetrator_account' => 's.devi (customer service representative)',
                    'refunds_flagged' => 6,
                    'total_amount_redirected' => 'RM 14,750.00',
                    'common_destination' => 'wallet W-8842',
                    'returned_items_on_file' => 0,
                ],
                'suspects' => [
                    ['name' => 'S. Devi', 'role' => 'Customer Service Representative', 'culprit' => true],
                    ['name' => 'Refund Approval Supervisor', 'role' => 'Approves refunds over RM1,000', 'culprit' => false],
                    ['name' => 'Finance Auditor', 'role' => 'Flagged the wallet pattern', 'culprit' => false],
                    ['name' => 'Payment Gateway Admin', 'role' => 'IT — manages wallet payout integration', 'culprit' => false],
                ],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-09-02', 'event' => 'First two fraudulent refunds issued, both routed to wallet W-8842.'],
                ['timestamp' => '2024-09-05 to 2024-09-15', 'event' => 'Four more refunds issued over the following ten days, same pattern, same destination wallet.'],
                ['timestamp' => '2024-09-16', 'event' => 'Finance audit query surfaces the shared wallet ID across all six refunds and escalates.'],
            ],
            'publish_at' => now()->subHours(4),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 55,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $caseA->id, 'question' => 'List every refund routed to wallet W-8842 — the order number and amount for each — and calculate the total.', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $caseA->id, 'question' => 'What common pattern links these six refunds despite being for different customers and order numbers?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $caseA->id, 'question' => 'Is there any evidence that physical items were actually returned for these orders? What does that tell you about the refund reasons given?', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $caseA->id, 'question' => 'How was this fraud eventually caught, and why might it have gone undetected for two weeks?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $caseA->id, 'question' => 'Recommend at least 3 controls that would prevent an employee from redirecting refunds to a personal account.', 'marks' => 20, 'display_order' => 5]);

        // ---- CASE 10: Mass Deletion (law firm document purge) ----
        $caseB = ForensicCase::create([
            'lecturer_id' => $lecturer1->id,
            'title' => 'Vanishing Act — Law Firm Document Purge',
            'incident_type' => 'mass_deletion',
            'difficulty' => 'advanced',
            'description' => 'An IT systems administrator, passed over for a promotion, deleted three years of active case documents and their version history over a weekend before the new hire\'s first day.',
            'scenario' => "Aldridge & Partners LLP discovered on Monday morning that its document management system (DMS) had lost every file under /dms/active_cases — 2,890 active case documents spanning three years of litigation work. Worse, the file-level version history for those same documents had also been purged, eliminating the usual recovery path. The firm's systems administrator, d.osman, had been informed by email on the preceding Friday that he would not be promoted to Head of IT — the role was instead going to an external hire starting that Monday. Server logs show d.osman connected via VPN early Saturday morning, well outside business hours. Investigate the audit trail to reconstruct exactly what was destroyed and assess whether this was retaliatory.",
            'learning_objectives' => "1. Distinguish primary data loss from destruction of the recovery path itself.\n2. Correlate a personnel decision with the timing of a destructive act.\n3. Reconstruct a precise sequence of events from minimal log evidence.\n4. Assess organisational risk from a single privileged account acting alone.\n5. Recommend offboarding and privileged-access controls for IT staff specifically.",
            'investigation_instructions' => "Step 1: Review the audit logs for d.osman's session on 2024-11-10.\nStep 2: Identify exactly what was deleted — documents and version history separately.\nStep 3: Note the session's start time, duration, and how it relates to the promotion decision on 2024-11-08.\nStep 4: Assess whether purging the version history changes how serious this incident is.\nStep 5: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'mass_deletion',
                'overview' => 'Audit trail showing a systems administrator deleting all active case documents and their version history in a single early-morning session, two days after a promotion decision went against him.',
                'audit_logs' => [
                    ['timestamp' => '2024-11-08 17:32:00', 'user' => 'hr_system', 'action' => 'EMAIL_SENT', 'ip' => '10.1.1.5', 'details' => "Subject: 'Head of IT Role — Decision' sent to d.osman (recorded in mail server logs, not the DMS)"],
                    ['timestamp' => '2024-11-10 02:18:47', 'user' => 'd.osman', 'action' => 'LOGIN_SUCCESS', 'ip' => '203.45.11.9', 'details' => 'Connected via VPN from a home IP — Saturday, well outside business hours'],
                    ['timestamp' => '2024-11-10 02:19:15', 'user' => 'd.osman', 'action' => 'QUERY_EXECUTED', 'ip' => '203.45.11.9', 'details' => "SELECT COUNT(*) FROM case_documents WHERE status='active' — Result: 2,890"],
                    ['timestamp' => '2024-11-10 02:19:40', 'user' => 'd.osman', 'action' => 'BULK_DELETE', 'ip' => '203.45.11.9', 'details' => '2,890 active case documents deleted from /dms/active_cases'],
                    ['timestamp' => '2024-11-10 02:20:05', 'user' => 'd.osman', 'action' => 'QUERY_EXECUTED', 'ip' => '203.45.11.9', 'details' => 'DELETE FROM document_version_history WHERE document_id IN (active case documents)'],
                    ['timestamp' => '2024-11-10 02:20:31', 'user' => 'd.osman', 'action' => 'BULK_DELETE', 'ip' => '203.45.11.9', 'details' => 'Version history purged for all 2,890 documents — recovery path eliminated'],
                    ['timestamp' => '2024-11-10 02:22:10', 'user' => 'd.osman', 'action' => 'LOGOUT', 'ip' => '203.45.11.9', 'details' => 'Session terminated. Total duration: 3 minutes 23 seconds'],
                    ['timestamp' => '2024-11-12 08:05:00', 'user' => 'admin_new', 'action' => 'LOGIN_FAILED', 'ip' => '10.1.1.20', 'details' => "New Head of IT's first day — case files inaccessible, incident reported to management"],
                ],
                'database_records' => [
                    ['summary' => 'Active case documents deleted', 'table' => 'case_documents', 'count' => 2890, 'date_range' => 'All active cases, 3 years of work', 'method' => "Bulk DELETE WHERE status='active'"],
                    ['summary' => 'Version history destroyed', 'table' => 'document_version_history', 'count' => 2890, 'date_range' => 'Matching the deleted documents', 'method' => 'Bulk DELETE WHERE document_id IN (...)'],
                ],
                'system_info' => [
                    'incident_date' => '2024-11-10',
                    'incident_time' => '02:18:47 - 02:22:10 (3 minutes 23 seconds)',
                    'affected_system' => 'Aldridge & Partners Document Management System',
                    'perpetrator_account' => 'd.osman (Systems Administrator)',
                    'documents_deleted' => 2890,
                    'version_history_also_destroyed' => true,
                    'promotion_decision_date' => '2024-11-08',
                    'new_hire_start_date' => '2024-11-11',
                ],
                'suspects' => [
                    ['name' => 'D. Osman', 'role' => 'IT Systems Administrator', 'culprit' => true],
                    ['name' => 'J. Fernandez', 'role' => 'Junior IT Support', 'culprit' => false],
                    ['name' => 'Backup Operations Contractor', 'role' => 'Third-party backup vendor', 'culprit' => false],
                    ['name' => 'Records Compliance Officer', 'role' => 'Oversees document retention policy', 'culprit' => false],
                ],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-11-08 17:32', 'event' => 'd.osman is informed by email that he will not get the Head of IT promotion.'],
                ['timestamp' => '2024-11-10 02:18', 'event' => 'd.osman connects via VPN from home, early Saturday morning.'],
                ['timestamp' => '2024-11-10 02:19', 'event' => 'All 2,890 active case documents are deleted.'],
                ['timestamp' => '2024-11-10 02:20', 'event' => 'The version history for those same documents is also purged, eliminating recovery.'],
                ['timestamp' => '2024-11-10 02:22', 'event' => 'Session ends after 3 minutes 23 seconds.'],
                ['timestamp' => '2024-11-11', 'event' => 'The external hire starts as the new Head of IT.'],
            ],
            'publish_at' => now()->subHours(3),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 75,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $caseB->id, 'question' => 'What two distinct destructive actions did d.osman take? Why does destroying the version history matter separately from deleting the documents themselves?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $caseB->id, 'question' => 'What is the significance of the timing — a Saturday at 02:18 AM, two days before the new Head of IT\'s first day?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $caseB->id, 'question' => 'How many documents were affected, and how long did the entire destructive session take from login to logout?', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $caseB->id, 'question' => 'Does the evidence suggest this was an impulsive act or a premeditated one? Justify your answer using specific details from the logs.', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $caseB->id, 'question' => 'Recommend at least 3 controls that would have prevented or limited the damage from this incident.', 'marks' => 20, 'display_order' => 5]);

        // ---- CASE 11: Brute Force (credential stuffing, not sequential brute force) ----
        $caseC = ForensicCase::create([
            'lecturer_id' => $lecturer2->id,
            'title' => 'Credential Bazaar — Patient Portal Credential Stuffing',
            'incident_type' => 'brute_force',
            'difficulty' => 'beginner',
            'description' => 'An attacker tried over a thousand different leaked username/password pairs against a hospital patient portal until one — reused from an unrelated breach — worked.',
            'scenario' => "Sunway Wellness Hospital's patient portal logged an unusual authentication pattern overnight: over a thousand login attempts in under three minutes, each using a DIFFERENT username, not the same one repeated. Security recognised this immediately as credential stuffing — testing a list of username/password pairs leaked from an unrelated 2023 breach of an online retailer, on the theory that some patients reuse the same password everywhere. Attempt 813 succeeded: a patient account, patient_devi87, had reused the exact password exposed in that unrelated breach. The attacker then accessed and downloaded that patient's full medical history. Investigate the logs to establish how this attack worked and what should be done differently from a normal brute-force response.",
            'learning_objectives' => "1. Distinguish credential stuffing from traditional single-account brute force.\n2. Recognise the signature pattern of a leaked-credential attack in login logs.\n3. Understand the risk of password reuse across unrelated services.\n4. Assess the scope of data exposed once access is gained.\n5. Recommend controls specific to credential stuffing, not just generic lockout policies.",
            'investigation_instructions' => "Step 1: Review the login attempt logs — note whether the same username or many different usernames were targeted.\nStep 2: Identify how many attempts were made in total and the success rate.\nStep 3: Identify which account succeeded and why, according to the evidence.\nStep 4: Determine what data was accessed after the successful login.\nStep 5: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'brute_force',
                'overview' => 'Authentication logs showing a large volume of login attempts against many different usernames — a leaked credential list — rather than repeated guesses against one account.',
                'audit_logs' => [
                    ['timestamp' => '2024-05-03 03:02:11', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '45.33.12.201', 'details' => 'Invalid credentials for username: j.tan88 [list entry 1]'],
                    ['timestamp' => '2024-05-03 03:02:14', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '45.33.12.201', 'details' => 'Invalid credentials for username: sarah_wong92 [list entry 2]'],
                    ['timestamp' => '2024-05-03 03:02:17', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '45.33.12.201', 'details' => 'Invalid credentials for username: ahmad.rosli [list entry 3]'],
                    ['timestamp' => '2024-05-03 03:04:52', 'user' => 'unknown', 'action' => 'LOGIN_FAILED', 'ip' => '45.33.12.201', 'details' => 'Invalid credentials for username: patient_kumar21 [list entry 812]'],
                    ['timestamp' => '2024-05-03 03:04:55', 'user' => 'patient_devi87', 'action' => 'LOGIN_SUCCESS', 'ip' => '45.33.12.201', 'details' => 'Password matched a credential leaked in an unrelated 2023 breach [list entry 813]'],
                    ['timestamp' => '2024-05-03 03:05:10', 'user' => 'patient_devi87', 'action' => 'FILE_ACCESSED', 'ip' => '45.33.12.201', 'details' => 'Navigated to /medical-records/history'],
                    ['timestamp' => '2024-05-03 03:05:34', 'user' => 'patient_devi87', 'action' => 'FILE_DOWNLOADED', 'ip' => '45.33.12.201', 'details' => 'Downloaded full medical history PDF — Size: 4.1MB'],
                    ['timestamp' => '2024-05-03 03:06:02', 'user' => 'patient_devi87', 'action' => 'LOGOUT', 'ip' => '45.33.12.201', 'details' => 'Session terminated. Duration: 67 seconds'],
                ],
                'network_logs' => [
                    ['timestamp' => '2024-05-03 03:02:11', 'source_ip' => '45.33.12.201', 'event' => 'Credential stuffing pattern detected: 1,204 distinct usernames tried in 2 minutes 44 seconds', 'count' => 1204],
                    ['timestamp' => '2024-05-03 03:05:34', 'source_ip' => '45.33.12.201', 'event' => 'Outbound file transfer following successful login', 'size' => '4.1MB'],
                ],
                'system_info' => [
                    'incident_date' => '2024-05-03',
                    'affected_system' => 'Sunway Wellness Hospital Patient Portal',
                    'total_attempts' => 1204,
                    'distinct_usernames_tried' => 1204,
                    'successful_attempt_number' => 813,
                    'success_rate' => '1 in 1,204',
                    'technique' => 'Credential stuffing (leaked credential list), not sequential brute force against one account',
                    'data_exposed' => "patient_devi87's full medical history (4.1MB PDF)",
                ],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-05-03 03:02', 'event' => 'Automated login attempts begin, cycling through 1,204 distinct usernames from a leaked credential list.'],
                ['timestamp' => '2024-05-03 03:04', 'event' => 'Attempt 813, for account patient_devi87, succeeds — that password had been reused from an unrelated 2023 breach.'],
                ['timestamp' => '2024-05-03 03:05', 'event' => "The attacker opens and downloads the patient's full medical history."],
                ['timestamp' => '2024-05-03 03:06', 'event' => 'Session ends. Total dwell time 67 seconds.'],
            ],
            'publish_at' => now()->subHours(2),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 40,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $caseC->id, 'question' => 'How does this attack pattern differ from a traditional brute-force attack against a single account? What specific evidence shows this?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $caseC->id, 'question' => 'How many total login attempts were made, and what was the success rate?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $caseC->id, 'question' => 'Why did this particular password work, according to the evidence? What does that imply about password reuse across services?', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $caseC->id, 'question' => 'What data was accessed and downloaded once the attacker gained access?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $caseC->id, 'question' => 'Recommend at least 3 controls that specifically address credential stuffing, distinct from generic brute-force lockout advice.', 'marks' => 20, 'display_order' => 5]);

        // ---- Demo enrollment data ----
        if ($students->count() >= 5) {
            $modelAnswers = [
                $caseA->id => [
                    "Six refunds were routed to wallet W-8842: ZB-10234 (RM2,450.00), ZB-10251 (RM1,980.00), ZB-10390 (RM3,120.00), ZB-10502 (RM2,700.00), ZB-10618 (RM1,850.00), and ZB-10777 (RM2,650.00) — a total of RM14,750.00.",
                    "All six refunds were processed by the same employee, s.devi, and all were routed to the exact same destination wallet, W-8842, despite being for six different customers and order numbers.",
                    "No — none of the six orders has a logged returned item on file, even though the stated reasons were 'item damaged' or 'item never arrived'. Legitimate damage/non-arrival refunds should have a matching return or investigation record, which is absent here.",
                    "A routine finance audit query on the refunds table surfaced that the same wallet ID appeared across six unrelated refunds, which is not something the refund system flags automatically since each refund looked individually valid at the time it was issued.",
                    "1) Require a second approver for any refund without a logged physical return. 2) Alert automatically when the same destination wallet/account appears across multiple refunds. 3) Restrict a single representative's authority to approve refunds above a threshold without review. 4) Periodically audit refund destinations against employee-linked accounts.",
                ],
                $caseB->id => [
                    "d.osman deleted all 2,890 active case documents, then separately deleted the version history for those same documents. Destroying the version history matters because it removes the normal recovery path — without it, the documents cannot be restored to an earlier state even if backups exist elsewhere.",
                    "d.osman was informed on Friday 8 November that he would not get the Head of IT promotion, and the new external hire was starting the following Monday, 11 November. Acting in the early hours of Saturday, two days before that start date, suggests he used his remaining access window deliberately before it would presumably be reviewed or restricted.",
                    "2,890 documents were deleted. The entire session — login to logout — lasted 3 minutes 23 seconds.",
                    "Premeditated. The session shows no exploratory browsing — just a COUNT query, the bulk document delete, then immediately the version-history delete, all within about 90 seconds. That sequence suggests he already knew exactly what to delete and in what order.",
                    "1) Suspend or restrict privileged access immediately once a resignation-adjacent decision (like being passed over) is communicated. 2) Require a second admin's approval for bulk deletes above a threshold. 3) Store version history in a separate system that a single admin account cannot also purge. 4) Alert on privileged logins during off-hours from remote IPs.",
                ],
                $caseC->id => [
                    "A traditional brute-force attack repeats guesses against ONE account. Here, 1,204 different usernames were each tried once — the log shows a new distinct username on every attempt, not repeated failures against the same account, which is the signature of a credential-stuffing attack using a pre-existing leaked username/password list.",
                    "1,204 total attempts were made, with exactly 1 succeeding — a success rate of 1 in 1,204.",
                    "The successful password had been leaked in an unrelated 2023 breach of a different online retailer, and the patient had reused that same password on the hospital portal. This shows password reuse across unrelated services is what let a breach elsewhere compromise this account.",
                    "The attacker accessed the patient's medical record history and downloaded a 4.1MB PDF containing the full medical history.",
                    "1) Check new/existing passwords against known-leaked-credential databases and reject matches. 2) Require multi-factor authentication for patient portal logins. 3) Rate-limit and flag logins that cycle through many distinct usernames from one IP in a short window, not just repeated attempts on one account.",
                ],
            ];

            $demo = [
                [0, $caseA, 'graded', 87],
                [2, $caseA, 'submitted', null],
                [1, $caseB, 'graded', 73],
                [3, $caseC, 'graded', 95],
                [4, $caseC, 'submitted', null],
            ];

            foreach ($demo as [$idx, $case, $status, $marks]) {
                $student = $students[$idx];

                $enrollment = CaseEnrollment::create([
                    'forensic_case_id' => $case->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'progress_percent' => 100,
                    'started_at' => now()->subHours(rand(4, 24)),
                    'submitted_at' => now()->subHours(rand(1, 3)),
                ]);

                $answersForCase = $modelAnswers[$case->id] ?? [];
                foreach ($case->questions()->orderBy('display_order')->get() as $i => $question) {
                    CaseAnswer::create([
                        'enrollment_id' => $enrollment->id,
                        'question_id' => $question->id,
                        'answer' => $answersForCase[$i] ?? 'Answer on file.',
                    ]);
                }

                CaseReport::create([
                    'enrollment_id' => $enrollment->id,
                    'student_name' => $student->name,
                    'student_id_number' => $student->student_id,
                    'program' => $student->program,
                    'findings' => 'The audit trail was reviewed in full, the responsible party identified from the evidence, and the sequence of events reconstructed with attention to why the timing and specific technique used matter to the investigation.',
                    'marks' => $marks,
                    'lecturer_feedback' => $marks !== null ? 'Clear identification of the pattern and the responsible party. Push the recommendations section further next time.' : null,
                    'status' => $marks !== null ? 'graded' : 'submitted',
                    'keystroke_count' => rand(1500, 3000),
                    'paste_count' => rand(0, 1),
                    'pasted_chars' => rand(0, 90),
                    'compose_seconds' => rand(1000, 2400),
                    'revision_count' => rand(3, 8),
                ]);
            }
        }

        $this->command->info('3 more cases seeded: Ghost Refund, Vanishing Act, Credential Bazaar.');
    }
}
