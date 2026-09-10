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
 * Two more full case scenarios authored by lecturer@forensicedu.test, taking
 * that account's own Case Registry from 3 cases to 5.
 */
class Lecturer1CasesSeeder extends Seeder
{
    public function run(): void
    {
        $lecturer1 = User::where('email', 'lecturer@forensicedu.test')->first();
        if (! $lecturer1) {
            $this->command->error('Expected lecturer not found — run the main DatabaseSeeder first.');
            return;
        }

        if (ForensicCase::where('title', 'Silent Markdown — E-Commerce Price Tampering')->exists()) {
            $this->command->warn('Lecturer 1 extra cases already exist — skipping.');
            return;
        }

        $students = User::where('role', 'student')->orderBy('student_id')->get();

        // ---- CASE 7: Unauthorized Modification (price tampering + self-purchase) ----
        $case7 = ForensicCase::create([
            'lecturer_id' => $lecturer1->id,
            'title' => 'Silent Markdown — E-Commerce Price Tampering',
            'incident_type' => 'unauthorized_modification',
            'difficulty' => 'advanced',
            'description' => 'A warehouse staff member with inventory-management access dropped the price of high-value electronics to near zero, bought them personally, then quietly restored the original prices.',
            'scenario' => "Zenith Mart's finance team flagged a discrepancy while reconciling October sales: four premium electronics SKUs sold far below cost on the same afternoon, and their prices were back to normal within the hour with no record of a sale or promotion approval. The inventory system shows warehouse operator k.tan changing all four prices to under RM1 at 14:02, placing a personal order for all four items between 14:04 and 14:09, then reverting every price back to its original value by 14:41 — before the daily price-audit report would have run. Investigate the price change, order, and reversion records to establish exactly what happened and reconstruct the financial impact.",
            'learning_objectives' => "1. Correlate database modification records with related business events (an order) to establish intent.\n2. Recognise an attempt to conceal fraud by reverting a change before routine review.\n3. Reconstruct a precise financial-loss figure from transactional evidence.\n4. Identify the access-control gap that allowed self-dealing.\n5. Recommend segregation-of-duties controls for pricing and purchasing.",
            'investigation_instructions' => "Step 1: Review the audit logs for product price changes on the incident date.\nStep 2: Identify which SKUs were changed, their original and modified prices, and who made the changes.\nStep 3: Check whether an order was placed while the discounted prices were active, and by whom.\nStep 4: Note when and by whom the prices were reverted, and how that timing relates to the daily audit report.\nStep 5: Calculate the financial impact.\nStep 6: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'unauthorized_modification',
                'overview' => 'Inventory audit trail showing four product prices dropped to near zero, a matching personal order placed within minutes, and the prices quietly restored before the routine daily audit.',
                'audit_logs' => [
                    ['timestamp' => '2024-10-14 14:02:03', 'user' => 'k.tan', 'action' => 'RECORD_MODIFIED', 'ip' => '10.2.14.9', 'details' => "UPDATE products SET price=0.50 WHERE sku='ELEC-4471' — OLD VALUE: 2499.00"],
                    ['timestamp' => '2024-10-14 14:02:19', 'user' => 'k.tan', 'action' => 'RECORD_MODIFIED', 'ip' => '10.2.14.9', 'details' => "UPDATE products SET price=0.50 WHERE sku='ELEC-4488' — OLD VALUE: 1899.00"],
                    ['timestamp' => '2024-10-14 14:02:35', 'user' => 'k.tan', 'action' => 'RECORD_MODIFIED', 'ip' => '10.2.14.9', 'details' => "UPDATE products SET price=0.50 WHERE sku='ELEC-4502' — OLD VALUE: 3299.00"],
                    ['timestamp' => '2024-10-14 14:02:51', 'user' => 'k.tan', 'action' => 'RECORD_MODIFIED', 'ip' => '10.2.14.9', 'details' => "UPDATE products SET price=0.50 WHERE sku='ELEC-4519' — OLD VALUE: 2199.00"],
                    ['timestamp' => '2024-10-14 14:04:12', 'user' => 'k.tan.personal', 'action' => 'ORDER_PLACED', 'ip' => '10.2.14.9', 'details' => 'Order #88213 — 4 items (ELEC-4471, ELEC-4488, ELEC-4502, ELEC-4519) — Total: RM2.00'],
                    ['timestamp' => '2024-10-14 14:09:47', 'user' => 'k.tan.personal', 'action' => 'PAYMENT_CONFIRMED', 'ip' => '10.2.14.9', 'details' => 'Order #88213 payment confirmed — Total charged: RM2.00'],
                    ['timestamp' => '2024-10-14 14:38:02', 'user' => 'k.tan', 'action' => 'RECORD_MODIFIED', 'ip' => '10.2.14.9', 'details' => "UPDATE products SET price=2499.00 WHERE sku='ELEC-4471' — reverted to original"],
                    ['timestamp' => '2024-10-14 14:41:19', 'user' => 'k.tan', 'action' => 'RECORD_MODIFIED', 'ip' => '10.2.14.9', 'details' => "UPDATE products SET price=2199.00 WHERE sku='ELEC-4519' — reverted to original (final SKU restored)"],
                ],
                'database_records' => [
                    ['id' => 'ELEC-4471', 'field' => 'price', 'table' => 'products', 'before' => 'RM 2,499.00', 'after' => 'RM 0.50', 'changed_at' => '2024-10-14 14:02:03'],
                    ['id' => 'ELEC-4488', 'field' => 'price', 'table' => 'products', 'before' => 'RM 1,899.00', 'after' => 'RM 0.50', 'changed_at' => '2024-10-14 14:02:19'],
                    ['id' => 'ELEC-4502', 'field' => 'price', 'table' => 'products', 'before' => 'RM 3,299.00', 'after' => 'RM 0.50', 'changed_at' => '2024-10-14 14:02:35'],
                    ['id' => 'ELEC-4519', 'field' => 'price', 'table' => 'products', 'before' => 'RM 2,199.00', 'after' => 'RM 0.50', 'changed_at' => '2024-10-14 14:02:51'],
                ],
                'system_info' => [
                    'incident_date' => '2024-10-14',
                    'affected_system' => 'Zenith Mart Inventory & Order System',
                    'perpetrator_account' => 'k.tan (warehouse operator)',
                    'skus_affected' => 4,
                    'original_combined_value' => 'RM 9,896.00',
                    'amount_paid' => 'RM 2.00',
                    'prices_reverted_before_daily_audit' => true,
                    'daily_audit_scheduled_time' => '15:00',
                ],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-10-14 14:02', 'event' => 'Four product prices are dropped to RM0.50 each within a 48-second window.'],
                ['timestamp' => '2024-10-14 14:04', 'event' => 'An order for all four discounted items is placed from the same IP address.'],
                ['timestamp' => '2024-10-14 14:09', 'event' => 'Payment of RM2.00 total is confirmed for goods worth RM9,896.00.'],
                ['timestamp' => '2024-10-14 14:38', 'event' => 'Prices begin being reverted to their original values.'],
                ['timestamp' => '2024-10-14 14:41', 'event' => 'The last price is restored — 19 minutes before the 15:00 daily audit report.'],
            ],
            'publish_at' => now()->subHours(3),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 70,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case7->id, 'question' => 'Which SKUs were modified, and what was the original price versus the modified price for each?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case7->id, 'question' => 'What evidence links the price changes to a personal benefit for the person who made them, rather than a legitimate pricing error?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case7->id, 'question' => 'What is significant about the timing of the price reversion relative to the daily audit report? What does this suggest about intent?', 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case7->id, 'question' => 'Calculate the financial loss from this incident — the gap between the original combined value of the items and what was actually paid.', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case7->id, 'question' => 'Recommend at least 3 controls that would prevent an employee from both setting a price and purchasing at that price.', 'marks' => 20, 'display_order' => 5]);

        // ---- CASE 8: Mass Deletion (research dataset, beginner) ----
        $case8 = ForensicCase::create([
            'lecturer_id' => $lecturer1->id,
            'title' => 'Wiped Overnight — Research Dataset Deletion',
            'incident_type' => 'mass_deletion',
            'difficulty' => 'beginner',
            'description' => 'Three years of shared research data disappeared from a university lab server overnight, hours after a graduate student was told he would not be listed as a co-author on an upcoming paper.',
            'scenario' => "The Applied Systems Lab's shared results server lost every file in its /experiments directory overnight — 2,150 files covering three years of recorded experiment data used by the whole research group. The deletion happened at 02:14 AM, roughly nine hours after the lab's principal investigator told graduate student grad_lim by email that he would not be included as a co-author on the group's upcoming publication. Server logs show grad_lim's account connected shortly before the deletion. Investigate the server logs to confirm what was deleted, by whom, and when, and assess how the surrounding context relates to the incident.",
            'learning_objectives' => "1. Identify a bulk file-deletion event from server logs.\n2. Correlate an account's activity with a precise deletion timestamp.\n3. Consider non-technical context (a personal or professional grievance) when assessing motive.\n4. Assess the scope of academic/research impact from data loss.\n5. Recommend backup and access-logging practices for shared research data.",
            'investigation_instructions' => "Step 1: Review the server logs for activity on the /experiments directory around 02:14 AM.\nStep 2: Identify the account involved, the command used, and the number of files affected.\nStep 3: Note how long the account was connected and what it did before the deletion.\nStep 4: Consider the scenario's context — what happened earlier that day — and how it might relate to the timing.\nStep 5: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'mass_deletion',
                'overview' => 'Server logs showing a single account connecting late at night and deleting the entire shared experiment-results directory.',
                'audit_logs' => [
                    ['timestamp' => '2024-09-19 17:42:00', 'user' => 'pi_hassan', 'action' => 'EMAIL_SENT', 'ip' => '10.10.5.2', 'details' => 'Subject: "Upcoming paper — author list" sent to grad_lim (recorded in lab mail server logs, not the file server)'],
                    ['timestamp' => '2024-09-20 02:14:01', 'user' => 'grad_lim', 'action' => 'LOGIN_SUCCESS', 'ip' => '10.10.5.41', 'details' => 'Connected to labserver via SSH from a lab workstation'],
                    ['timestamp' => '2024-09-20 02:14:22', 'user' => 'grad_lim', 'action' => 'QUERY_EXECUTED', 'ip' => '10.10.5.41', 'details' => 'rm -rf /shared/experiments/*'],
                    ['timestamp' => '2024-09-20 02:14:23', 'user' => 'grad_lim', 'action' => 'BULK_DELETE', 'ip' => '10.10.5.41', 'details' => '2,150 files deleted from /shared/experiments — spanning 2021-2024'],
                    ['timestamp' => '2024-09-20 02:15:40', 'user' => 'grad_lim', 'action' => 'LOGOUT', 'ip' => '10.10.5.41', 'details' => 'Session closed. Total duration: 1 minute 39 seconds'],
                ],
                'database_records' => [
                    ['summary' => 'Experiment result files deleted', 'table' => '/shared/experiments (filesystem)', 'count' => 2150, 'date_range' => '2021-01-01 to 2024-09-19', 'method' => 'rm -rf /shared/experiments/*'],
                ],
                'system_info' => [
                    'incident_date' => '2024-09-20',
                    'incident_time' => '02:14:01 - 02:15:40 (1 minute 39 seconds)',
                    'affected_system' => 'Applied Systems Lab shared results server',
                    'perpetrator_account' => 'grad_lim',
                    'files_deleted' => 2150,
                    'data_span' => '3 years (2021-2024)',
                    'context' => 'Account holder was informed 9 hours earlier that he would not be a co-author on the group\'s upcoming paper',
                    'backups_available' => 'Unknown — under investigation',
                ],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-09-19 17:42', 'event' => 'The principal investigator emails grad_lim that he will not be a co-author on the upcoming paper.'],
                ['timestamp' => '2024-09-20 02:14', 'event' => 'grad_lim connects to the lab server via SSH.'],
                ['timestamp' => '2024-09-20 02:14', 'event' => 'A single command deletes all 2,150 files in the shared experiments directory.'],
                ['timestamp' => '2024-09-20 02:15', 'event' => 'Session ends after 1 minute 39 seconds.'],
            ],
            'publish_at' => now()->subHours(2),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 40,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case8->id, 'question' => 'Which account performed the deletion, what command was used, and how many files were affected?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case8->id, 'question' => 'How long was the account connected before the deletion occurred, and what does that suggest about how deliberate the action was?', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case8->id, 'question' => "What happened earlier that day that could be relevant to grad_lim's motive? Explain how you would treat this context as an investigator — as proof, or as something to ask about?", 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case8->id, 'question' => 'What is the research impact of losing three years of experiment data, and what should the lab check for immediately after discovering this?', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case8->id, 'question' => 'Recommend at least 3 controls (technical or procedural) that would have limited the damage from this incident.', 'marks' => 20, 'display_order' => 5]);

        // ---- Demo enrollment data ----
        if ($students->count() >= 5) {
            $answers7 = [
                "Four SKUs were changed: ELEC-4471 (RM2,499.00 to RM0.50), ELEC-4488 (RM1,899.00 to RM0.50), ELEC-4502 (RM3,299.00 to RM0.50), and ELEC-4519 (RM2,199.00 to RM0.50) — all dropped to RM0.50 within a 48-second window.",
                "An order for exactly those four discounted SKUs was placed from the same IP address as the price changes, just two minutes after the last price was modified, and paid within five minutes. The person who set the prices also made the purchase, which is not consistent with a routine pricing update.",
                "All four prices were restored by 14:41, only 19 minutes before the scheduled 15:00 daily audit report. Reverting the prices just before the report that would have surfaced the discrepancy suggests a deliberate attempt to avoid detection, not an accidental oversight later corrected.",
                "The four items had a combined original value of RM9,896.00, and only RM2.00 was actually paid — a loss of RM9,894.00.",
                "1) Require a second approver for any price change below a defined percentage of normal price. 2) Block employees from purchasing items they have permission to price. 3) Alert on any price change followed by an order from the same account or IP within a short window. 4) Run audit checks more frequently than once a day.",
            ];
            $answers8 = [
                "The account grad_lim connected via SSH and ran 'rm -rf /shared/experiments/*', which deleted 2,150 files.",
                "The account was connected for only 1 minute 39 seconds, with the deletion command run about 20 seconds after login. This is consistent with someone who already knew exactly what they intended to do rather than someone exploring the system before deciding to act.",
                "grad_lim had been told just 9 hours earlier that he would not be a co-author on the group's paper. This is a plausible motive, but on its own it is circumstantial — an investigator should treat it as a reason to ask grad_lim directly, not as proof he acted out of retaliation.",
                "Three years of experiment data supporting the group's ongoing and upcoming research is gone, which could delay or invalidate the paper the deletion appears to be connected to. The lab should immediately check whether any backup of /shared/experiments exists and how recent it is.",
                "1) Maintain regular, access-separated backups of shared research data. 2) Restrict destructive filesystem commands on shared directories to designated administrators. 3) Log and alert on bulk deletions in real time rather than discovering them the next day.",
            ];

            $demo = [
                [0, $case7, 'graded', 85, $answers7],
                [4, $case7, 'submitted', null, $answers7],
                [1, $case8, 'graded', 69, $answers8],
            ];

            foreach ($demo as [$idx, $case, $status, $marks, $answers]) {
                $student = $students[$idx];

                $enrollment = CaseEnrollment::create([
                    'forensic_case_id' => $case->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'progress_percent' => 100,
                    'started_at' => now()->subHours(rand(4, 24)),
                    'submitted_at' => now()->subHours(rand(1, 3)),
                ]);

                foreach ($case->questions()->orderBy('display_order')->get() as $i => $question) {
                    CaseAnswer::create([
                        'enrollment_id' => $enrollment->id,
                        'question_id' => $question->id,
                        'answer' => $answers[$i],
                    ]);
                }

                CaseReport::create([
                    'enrollment_id' => $enrollment->id,
                    'student_name' => $student->name,
                    'student_id_number' => $student->student_id,
                    'program' => $student->program,
                    'findings' => 'The audit trail was reviewed in full and cross-referenced with the surrounding timeline to establish both what happened and why the timing of each action matters to the investigation.',
                    'marks' => $marks,
                    'lecturer_feedback' => $marks !== null ? 'Clear reconstruction of the sequence of events. Push the analysis of motive/intent a bit further next time.' : null,
                    'status' => $marks !== null ? 'graded' : 'submitted',
                    'keystroke_count' => rand(1500, 3000),
                    'paste_count' => rand(0, 1),
                    'pasted_chars' => rand(0, 90),
                    'compose_seconds' => rand(1000, 2400),
                    'revision_count' => rand(3, 8),
                ]);
            }
        }

        $this->command->info('Lecturer 1 extra cases seeded: Silent Markdown, Wiped Overnight.');
    }
}
