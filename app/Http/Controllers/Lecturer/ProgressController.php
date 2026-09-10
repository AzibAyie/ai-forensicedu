<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseEnrollment;
use App\Models\ForensicCase;
use App\Models\User;

class ProgressController extends Controller
{
    public function index()
    {
        $lecturerId = auth()->id();
        $caseIds = ForensicCase::where('lecturer_id', $lecturerId)->pluck('id');

        $students = User::where('role', 'student')
            ->where('lecturer_id', $lecturerId)
            ->with(['enrollments' => fn ($q) => $q->whereIn('forensic_case_id', $caseIds)->with('forensicCase', 'report')])
            ->orderBy('name')
            ->get();

        $rows = $students->map(function ($s) {
            $es = $s->enrollments;
            $active = $es->firstWhere(fn ($e) => in_array($e->status, ['unlocked', 'in_progress']));
            $done = $es->filter(fn ($e) => in_array($e->status, ['submitted', 'graded']));

            $lastSeen = ActivityLog::where('user_id', $s->id)->latest()->first()?->created_at;

            $graded = $done->filter(fn ($e) => $e->report?->marks !== null);
            $avg = $graded->count()
                ? round($graded->avg(fn ($e) => $e->report->marks / max($e->forensicCase->total_marks, 1) * 100))
                : null;

            // Stalled: an open case with no activity for 5+ days
            $stalled = $active && $lastSeen && $lastSeen->diffInDays(now()) >= 5;

            return [
                'student' => $s,
                'active' => $active,
                'completed' => $done->count(),
                'enrolled' => $es->count(),
                'avg' => $avg,
                'last_seen' => $lastSeen,
                'stalled' => $stalled,
                'idle' => $es->isEmpty(),
            ];
        });

        $summary = [
            'total' => $rows->count(),
            'active' => $rows->filter(fn ($r) => $r['active'])->count(),
            'stalled' => $rows->filter(fn ($r) => $r['stalled'])->count(),
            'idle' => $rows->filter(fn ($r) => $r['idle'])->count(),
        ];

        return view('lecturer.progress.index', compact('rows', 'summary'));
    }

    public function show(User $student)
    {
        if ($student->lecturer_id !== auth()->id()) {
            abort(403);
        }

        $caseIds = ForensicCase::where('lecturer_id', auth()->id())->pluck('id');

        $enrollments = CaseEnrollment::where('student_id', $student->id)
            ->whereIn('forensic_case_id', $caseIds)
            ->with('forensicCase', 'report', 'answers')
            ->latest()->get();

        $activity = ActivityLog::where('user_id', $student->id)
            ->latest()->limit(40)->get();

        // Breakdown of this student's cases by stage, for the progress pie chart.
        // Covers every case they've touched, not just graded ones, so it's
        // meaningful even before any grading has happened.
        $notStarted = ForensicCase::whereIn('id', $caseIds)->available()
            ->whereNotIn('id', $enrollments->pluck('forensic_case_id'))
            ->count();

        $stageCounts = [
            'Graded' => $enrollments->filter(fn ($e) => $e->report?->marks !== null)->count(),
            'Awaiting grade' => $enrollments->filter(fn ($e) => $e->isSubmitted() && $e->report?->marks === null)->count(),
            'In progress' => $enrollments->filter(fn ($e) => !$e->isSubmitted())->count(),
            'Not started' => $notStarted,
        ];

        return view('lecturer.progress.show', compact('student', 'enrollments', 'activity', 'stageCounts'));
    }
}
