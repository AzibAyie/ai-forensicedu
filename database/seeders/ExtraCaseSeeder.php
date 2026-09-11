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
 * A sixth, fully-detailed case scenario — a mass-deletion incident with a
 * data-exfiltration twist, giving the mass_deletion incident type a second
 * example and rounding the difficulty spread to 2 beginner / 2 intermediate
 * / 2 advanced across all six cases.
 */
class ExtraCaseSeeder extends Seeder
{
    public function run(): void
    {
        $lecturer2 = User::where('email', 'lecturer2@forensicedu.test')->first();
        if (! $lecturer2) {
            $this->command->error('Expected lecturer not found — run the main DatabaseSeeder first.');
            return;
        }

        if (ForensicCase::where('title', 'Operation Clean Sweep — Patient Records Deletion')->exists()) {
            $this->command->warn('Extra case already exists — skipping.');
            return;
        }

        $students = User::where('role', 'student')->orderBy('student_id')->get();

        $case6 = ForensicCase::create([
            'lecturer_id' => $lecturer2->id,
            'title' => 'Operation Clean Sweep — Patient Records Deletion',
            'incident_type' => 'mass_deletion',
            'difficulty' => 'advanced',
            'description' => 'An offboarding contractor exported a copy of patient visit records for personal use, then deleted the originals and all upcoming appointments before their access was due to be revoked.',
            'scenario' => "Meridian Health Group's IT team discovered that patient visit histories across all three of its clinics had vanished from the Patient Records Management System (PRMS). The deletion was traced to contractor_reyes, an external contractor whose system access was scheduled for revocation on 7 November 2024 following notice given the previous week. Late on the night of 5 November — two days before that access was due to be cut off — the account connected via VPN, exported a full copy of patient visit data to a local device, then deleted 1,240 patient visit records and 890 upcoming appointments from the live system. No backup of the deleted records exists on the PRMS server itself; only the export the contractor made survived. Investigate the audit trail to establish exactly what happened, in what order, and why the timing matters.",
            'learning_objectives' => "1. Distinguish data exfiltration from data destruction in a single incident.\n2. Correlate the timing of an incident with external context (offboarding, access revocation) to assess motive.\n3. Reconstruct a precise sequence of events from audit log timestamps.\n4. Assess the combined risk of theft and sabotage by a departing user.\n5. Recommend offboarding and contractor-access controls.",
            'investigation_instructions' => "Step 1: Review the audit logs for contractor_reyes's session on 2024-11-05.\nStep 2: Identify what was exported, and when, relative to the deletions that followed.\nStep 3: Identify exactly what was deleted — which tables, how many records, and what date ranges were affected.\nStep 4: Note how the incident date relates to the scheduled access-revocation date mentioned in the scenario.\nStep 5: Assess whether this looks like sabotage, theft, or both.\nStep 6: Answer all investigation questions and write your report.",
            'simulated_evidence' => [
                'type' => 'mass_deletion',
                'overview' => 'Audit trail showing a departing contractor exporting patient data before deleting it from the live system, timed shortly before their access was due to be revoked.',
                'audit_logs' => [
                    ['timestamp' => '2024-11-05 23:38:02', 'user' => 'contractor_reyes', 'action' => 'LOGIN_SUCCESS', 'ip' => '10.50.2.19', 'details' => 'Connected via VPN outside contracted working hours'],
                    ['timestamp' => '2024-11-05 23:38:44', 'user' => 'contractor_reyes', 'action' => 'QUERY_EXECUTED', 'ip' => '10.50.2.19', 'details' => "SELECT * FROM patient_visits WHERE clinic_id IN (1,2,3)"],
                    ['timestamp' => '2024-11-05 23:39:12', 'user' => 'contractor_reyes', 'action' => 'FILE_EXPORTED', 'ip' => '10.50.2.19', 'details' => 'patient_visits_backup_nov2024.csv — 1,240 rows exported to local device'],
                    ['timestamp' => '2024-11-05 23:40:05', 'user' => 'contractor_reyes', 'action' => 'QUERY_EXECUTED', 'ip' => '10.50.2.19', 'details' => 'DELETE FROM patient_visits WHERE clinic_id IN (1,2,3)'],
                    ['timestamp' => '2024-11-05 23:40:06', 'user' => 'contractor_reyes', 'action' => 'BULK_DELETE', 'ip' => '10.50.2.19', 'details' => '1,240 patient visit records deleted across 3 clinics'],
                    ['timestamp' => '2024-11-05 23:40:22', 'user' => 'contractor_reyes', 'action' => 'QUERY_EXECUTED', 'ip' => '10.50.2.19', 'details' => "DELETE FROM appointments WHERE appointment_date >= '2024-11-06'"],
                    ['timestamp' => '2024-11-05 23:40:23', 'user' => 'contractor_reyes', 'action' => 'BULK_DELETE', 'ip' => '10.50.2.19', 'details' => '890 upcoming appointment records deleted'],
                    ['timestamp' => '2024-11-05 23:41:15', 'user' => 'contractor_reyes', 'action' => 'LOGOUT', 'ip' => '10.50.2.19', 'details' => 'Session terminated. Total duration: 3 minutes 13 seconds'],
                ],
                'database_records' => [
                    ['summary' => 'Patient visit records deleted', 'table' => 'patient_visits', 'count' => 1240, 'date_range' => 'All clinics (1, 2, 3)', 'method' => 'Bulk DELETE WHERE clinic_id IN (1,2,3)'],
                    ['summary' => 'Upcoming appointments deleted', 'table' => 'appointments', 'count' => 890, 'date_range' => 'From 2024-11-06 onward', 'method' => "Bulk DELETE WHERE appointment_date >= '2024-11-06'"],
                    ['summary' => 'Data exported before deletion', 'table' => 'patient_visits', 'count' => 1240, 'date_range' => 'All clinics (1, 2, 3)', 'method' => 'Full SELECT, exported to patient_visits_backup_nov2024.csv'],
                ],
                'system_info' => [
                    'incident_date' => '2024-11-05',
                    'incident_time' => '23:38:02 - 23:41:15 (3 minutes 13 seconds)',
                    'affected_system' => 'Meridian Health Group Patient Records Management System',
                    'perpetrator_account' => 'contractor_reyes (external contractor)',
                    'scheduled_access_revocation' => '2024-11-07 (2 days after the incident)',
                    'records_deleted' => '1,240 patient visits + 890 upcoming appointments',
                    'data_exported_first' => true,
                ],
                'suspects' => [
                    ['name' => 'contractor_reyes', 'role' => 'Offboarding External Contractor', 'culprit' => true],
                    ['name' => 'IT Security Auditor', 'role' => 'Reviews contractor access', 'culprit' => false],
                    ['name' => 'Permanent Records Clerk', 'role' => 'Full-time patient records staff', 'culprit' => false],
                    ['name' => 'Second Contractor', 'role' => 'Also had temporary access', 'culprit' => false],
                ],
            ],
            'timeline_events' => [
                ['timestamp' => '2024-10-29', 'event' => 'Contractor Reyes is notified that system access will be revoked on 7 November.'],
                ['timestamp' => '2024-11-05 23:38', 'event' => 'contractor_reyes logs in via VPN, well outside contracted hours.'],
                ['timestamp' => '2024-11-05 23:39', 'event' => 'Full patient visit dataset exported to a local device before any deletion occurs.'],
                ['timestamp' => '2024-11-05 23:40', 'event' => '1,240 patient visit records and 890 upcoming appointments are deleted in two bulk operations 17 seconds apart.'],
                ['timestamp' => '2024-11-05 23:41', 'event' => 'Session ends. Total time on system: 3 minutes 13 seconds.'],
            ],
            'publish_at' => now()->subHours(6),
            'close_at' => now()->addDays(21),
            'is_published' => true,
            'is_locked' => false,
            'expected_duration' => 75,
            'total_marks' => 100,
        ]);

        CaseQuestion::create(['forensic_case_id' => $case6->id, 'question' => 'What data was exported before the deletion occurred, and why does the order of these two actions matter to the investigation?', 'marks' => 20, 'display_order' => 1]);
        CaseQuestion::create(['forensic_case_id' => $case6->id, 'question' => 'List exactly what was deleted — the tables, record counts, and date ranges affected — and the total time span of the incident.', 'marks' => 20, 'display_order' => 2]);
        CaseQuestion::create(['forensic_case_id' => $case6->id, 'question' => "What is the significance of the incident's timing relative to the contractor's scheduled access revocation date? What does this suggest about motive?", 'marks' => 20, 'display_order' => 3]);
        CaseQuestion::create(['forensic_case_id' => $case6->id, 'question' => 'Was this incident sabotage, data theft, or both? Justify your answer using the specific evidence available.', 'marks' => 20, 'display_order' => 4]);
        CaseQuestion::create(['forensic_case_id' => $case6->id, 'question' => 'Recommend at least 3 controls that would prevent a departing contractor or employee from causing this kind of damage.', 'marks' => 20, 'display_order' => 5]);

        // ---- Demo enrollment data so the case shows up in the gradebook too ----
        if ($students->count() >= 5) {
            $modelAnswers = [
                "The full patient_visits table (1,240 rows) was exported to a local CSV file one minute before the deletions began. Because the export happened first, this was not a panicked cover-up after the fact — the contractor secured a personal copy of the data before destroying the original, which points to intentional data theft alongside the sabotage.",
                "1,240 patient visit records across all three clinics and 890 upcoming appointment records (dated 6 November onward) were deleted. Both bulk deletions happened within the same session, 17 seconds apart, and the entire session — login to logout — lasted 3 minutes 13 seconds.",
                "The deletion occurred on 5 November, only two days before the contractor's access was due to be formally revoked on 7 November. Acting just before a scheduled cutoff, rather than at a random time, strongly suggests the contractor knew their window to act was closing and deliberately used their remaining access before it disappeared.",
                "Both. The export of a full data copy before any deletion is clear evidence of data theft, while the subsequent deletion of the live records and upcoming appointments — with no legitimate business reason — constitutes sabotage. The two actions are connected: the theft ensured the contractor kept a copy for themselves while denying it to the organisation.",
                "1) Revoke system access immediately when offboarding is announced, rather than leaving it active until the official end date. 2) Require approval and logging for any bulk export of patient data. 3) Restrict contractor accounts from bulk delete operations entirely. 4) Alert on large data exports or deletions outside business hours.",
            ];

            $demo = [
                [1, 'graded', 79],
                [3, 'submitted', null],
            ];

            foreach ($demo as [$idx, $status, $marks]) {
                $student = $students[$idx];

                $enrollment = CaseEnrollment::create([
                    'forensic_case_id' => $case6->id,
                    'student_id' => $student->id,
                    'status' => $status,
                    'progress_percent' => 100,
                    'started_at' => now()->subHours(rand(4, 20)),
                    'submitted_at' => now()->subHours(rand(1, 3)),
                ]);

                foreach ($case6->questions()->orderBy('display_order')->get() as $i => $question) {
                    CaseAnswer::create([
                        'enrollment_id' => $enrollment->id,
                        'question_id' => $question->id,
                        'answer' => $modelAnswers[$i],
                    ]);
                }

                CaseReport::create([
                    'enrollment_id' => $enrollment->id,
                    'student_name' => $student->name,
                    'student_id_number' => $student->student_id,
                    'program' => $student->program,
                    'findings' => 'The audit trail shows a full data export immediately preceding two bulk deletions, timed two days before the account\'s scheduled access revocation. This combination of theft followed by destruction, executed in a single short session just before access was due to be cut off, indicates deliberate action rather than an accident or routine maintenance.',
                    'marks' => $marks,
                    'lecturer_feedback' => $marks !== null ? 'Good identification of the export-before-delete sequence. Develop the recommendations section further next time.' : null,
                    'status' => $marks !== null ? 'graded' : 'submitted',
                    'keystroke_count' => rand(1500, 3000),
                    'paste_count' => rand(0, 1),
                    'pasted_chars' => rand(0, 90),
                    'compose_seconds' => rand(1000, 2400),
                    'revision_count' => rand(3, 8),
                ]);
            }
        }

        $this->command->info('Extra case seeded: Operation Clean Sweep.');
    }
}
