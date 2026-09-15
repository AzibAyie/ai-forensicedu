<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseEnrollment;
use App\Models\ForensicCase;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $currentEnrollment = $user->currentEnrollment();
        $hasActiveCase = $currentEnrollment !== null;

        $completedEnrollments = $user->enrollments()
            ->with('forensicCase', 'report')
            ->whereIn('status', ['submitted', 'graded'])
            ->latest()
            ->get();

        $availableCases = ForensicCase::available()
            ->whereNotIn('id', $user->enrollments()->pluck('forensic_case_id'))
            ->with('lecturer')
            ->latest()
            ->get();

        // Real streak: consecutive most-recent graded reports scoring 50%+,
        // counted back from today until the first below-50% or ungraded break.
        $gradedForStreak = $user->enrollments()
            ->whereHas('report', fn ($q) => $q->whereNotNull('marks'))
            ->with('report', 'forensicCase')
            ->get()
            ->sortByDesc(fn ($e) => $e->submitted_at)
            ->values();

        $streak = 0;
        foreach ($gradedForStreak as $e) {
            $pct = ($e->report->marks / max($e->forensicCase->total_marks, 1)) * 100;
            if ($pct < 50) {
                break;
            }
            $streak++;
        }

        $stats = [
            'total_cases' => $user->enrollments()->count(),
            'completed' => $completedEnrollments->count(),
            'in_progress' => $hasActiveCase ? 1 : 0,
            'streak' => $streak,
            'avg_score' => $user->enrollments()
                ->whereHas('report', fn ($q) => $q->whereNotNull('marks'))
                ->with('report')
                ->get()
                ->avg(fn ($e) => $e->report?->marks) ?? 0,
        ];

        $recentActivity = ActivityLog::where('user_id', $user->id)
            ->latest()->limit(5)->get();

        $xp = $completedEnrollments->count() * 50 + (int) $completedEnrollments->sum(fn ($e) => $e->report?->marks ?? 0);
        $rank = $this->buildRank($xp);

        $boardCleared = ! $hasActiveCase && $availableCases->isEmpty() && $completedEnrollments->count() > 0;
        $badges = $this->buildBadges($user, $completedEnrollments, $stats, $boardCleared);

        return view('student.dashboard.index', compact(
            'currentEnrollment', 'hasActiveCase',
            'availableCases', 'completedEnrollments',
            'stats', 'recentActivity', 'rank', 'badges'
        ));
    }

    /** Rank tiers driven by XP (50 per completed case + report marks earned). */
    private function buildRank(int $xp): array
    {
        $tiers = [
            ['name' => 'Recruit', 'min' => 0, 'icon' => 'user'],
            ['name' => 'Junior Investigator', 'min' => 100, 'icon' => 'search'],
            ['name' => 'Field Investigator', 'min' => 300, 'icon' => 'file-search'],
            ['name' => 'Senior Investigator', 'min' => 600, 'icon' => 'shield'],
            ['name' => 'Lead Detective', 'min' => 1000, 'icon' => 'shield-check'],
            ['name' => 'Master Forensic Analyst', 'min' => 1500, 'icon' => 'award'],
            ['name' => 'Chief Inspector', 'min' => 2500, 'icon' => 'crown'],
        ];

        $currentIndex = 0;
        foreach ($tiers as $i => $tier) {
            if ($xp >= $tier['min']) {
                $currentIndex = $i;
            }
        }

        $current = $tiers[$currentIndex];
        $next = $tiers[$currentIndex + 1] ?? null;

        $progress = $next
            ? (int) round((($xp - $current['min']) / max($next['min'] - $current['min'], 1)) * 100)
            : 100;

        return [
            'name' => $current['name'],
            'icon' => $current['icon'],
            'xp' => $xp,
            'next_name' => $next['name'] ?? null,
            'next_xp' => $next['min'] ?? null,
            'progress' => $progress,
        ];
    }

    /** Achievement badges computed on the fly from existing enrollment/report/activity data. */
    private function buildBadges($user, $completedEnrollments, array $stats, bool $boardCleared): array
    {
        $hintCaseIds = ActivityLog::where('user_id', $user->id)
            ->where('action', 'HINT_VIEWED')
            ->pluck('forensic_case_id');

        $hasPerfect = $completedEnrollments->contains(
            fn ($e) => $e->report?->marks !== null && $e->forensicCase && $e->report->marks >= $e->forensicCase->total_marks
        );

        $hasNoHintCompletion = $completedEnrollments->contains(
            fn ($e) => ! $hintCaseIds->contains($e->forensic_case_id)
        );

        $hasBeatClock = $completedEnrollments->contains(function ($e) {
            if (! $e->started_at || ! $e->submitted_at || ! $e->forensicCase?->expected_duration) {
                return false;
            }

            return $e->started_at->diffInMinutes($e->submitted_at) < $e->forensicCase->expected_duration;
        });

        $hasCorrectAccusation = $completedEnrollments->contains('accusation_correct', true);

        return [
            ['key' => 'first_case', 'icon' => 'zap', 'label' => 'Case Zero', 'description' => 'Complete your first case.', 'unlocked' => $completedEnrollments->count() >= 1],
            ['key' => 'perfect_report', 'icon' => 'star', 'label' => 'Perfect Report', 'description' => 'Score full marks on a report.', 'unlocked' => $hasPerfect],
            ['key' => 'no_hints', 'icon' => 'brain', 'label' => 'Independent Investigator', 'description' => 'Complete a case without using any hints.', 'unlocked' => $hasNoHintCompletion],
            ['key' => 'beat_clock', 'icon' => 'timer', 'label' => 'Beat the Clock', 'description' => "Submit faster than the case's expected duration.", 'unlocked' => $hasBeatClock],
            ['key' => 'sharp_eye', 'icon' => 'crosshair', 'label' => 'Sharp Eye', 'description' => 'Correctly identify a suspect.', 'unlocked' => $hasCorrectAccusation],
            ['key' => 'on_a_roll', 'icon' => 'flame', 'label' => 'On a Roll', 'description' => 'Reach a streak of 3 consecutive passing grades.', 'unlocked' => $stats['streak'] >= 3],
            ['key' => 'veteran', 'icon' => 'medal', 'label' => 'Veteran Investigator', 'description' => 'Complete 5 cases.', 'unlocked' => $completedEnrollments->count() >= 5],
            ['key' => 'board_cleared', 'icon' => 'check-check', 'label' => 'Board Cleared', 'description' => 'Complete every case currently assigned to you.', 'unlocked' => $boardCleared],
        ];
    }

    public function unlockCase(Request $request, ForensicCase $forensicCase)
    {
        $user = auth()->user();

        if ($user->hasActiveCase()) {
            return back()->withErrors(['case' => 'You must submit your current case before starting a new one.']);
        }

        $existing = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', $user->id)->first();
        if ($existing) {
            return redirect()->route('student.case.show', $forensicCase);
        }

        if (! $forensicCase->isAvailable()) {
            return back()->withErrors(['case' => 'This case is not open right now.']);
        }

        if ($forensicCase->is_locked) {
            $request->validate(['password' => 'required|string']);
            if (! $forensicCase->checkPassword($request->password)) {
                return back()->withErrors(['password' => 'Incorrect case password.']);
            }
        }

        $enrollment = CaseEnrollment::create([
            'forensic_case_id' => $forensicCase->id,
            'student_id' => $user->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'progress_percent' => 5,
        ]);

        ActivityLog::record('CASE_STARTED', "Started case: {$forensicCase->title}", $forensicCase->id);

        return redirect()->route('student.case.show', $forensicCase)
            ->with('success', 'Case unlocked! Begin your investigation.');
    }

    public function profile()
    {
        return view('student.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'faculty' => 'nullable|string|max:255',
            'program' => 'nullable|string|max:255',
        ]);
        $user->update($request->only('name', 'phone', 'faculty', 'program'));

        return back()->with('success', 'Profile updated successfully.');
    }
}
