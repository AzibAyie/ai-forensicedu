<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the investigator rank and achievement badges
 * shown on the student dashboard, the record page, and the sidebar. All of
 * it is computed on the fly from existing enrollment/report/activity data.
 */
class Achievements
{
    private const RANK_TIERS = [
        ['name' => 'Recruit', 'min' => 0, 'icon' => 'user'],
        ['name' => 'Junior Investigator', 'min' => 100, 'icon' => 'search'],
        ['name' => 'Field Investigator', 'min' => 300, 'icon' => 'file-search'],
        ['name' => 'Senior Investigator', 'min' => 600, 'icon' => 'shield'],
        ['name' => 'Lead Detective', 'min' => 1000, 'icon' => 'shield-check'],
        ['name' => 'Master Forensic Analyst', 'min' => 1500, 'icon' => 'award'],
        ['name' => 'Chief Inspector', 'min' => 2500, 'icon' => 'crown'],
    ];

    public static function completedEnrollments(User $user): Collection
    {
        return $user->enrollments()
            ->with('forensicCase', 'report')
            ->whereIn('status', ['submitted', 'graded'])
            ->latest()
            ->get();
    }

    /** 50 XP per completed case, plus every mark earned on its report. */
    public static function xp(Collection $completedEnrollments): int
    {
        return $completedEnrollments->count() * 50
            + (int) $completedEnrollments->sum(fn ($e) => $e->report?->marks ?? 0);
    }

    /** Consecutive most-recent graded reports scoring 50%+, counted back from today. */
    public static function streak(User $user): int
    {
        $graded = $user->enrollments()
            ->whereHas('report', fn ($q) => $q->whereNotNull('marks'))
            ->with('report', 'forensicCase')
            ->get()
            ->sortByDesc(fn ($e) => $e->submitted_at)
            ->values();

        $streak = 0;
        foreach ($graded as $e) {
            $pct = ($e->report->marks / max($e->forensicCase->total_marks, 1)) * 100;
            if ($pct < 50) {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    public static function boardCleared(User $user, Collection $completedEnrollments, bool $hasActiveCase): bool
    {
        $availableCasesEmpty = ! $user->lecturer_id || ForensicCase::available()
            ->where('lecturer_id', $user->lecturer_id)
            ->whereNotIn('id', $user->enrollments()->pluck('forensic_case_id'))
            ->doesntExist();

        return ! $hasActiveCase && $availableCasesEmpty && $completedEnrollments->count() > 0;
    }

    public static function rank(int $xp): array
    {
        $currentIndex = 0;
        foreach (self::RANK_TIERS as $i => $tier) {
            if ($xp >= $tier['min']) {
                $currentIndex = $i;
            }
        }

        $current = self::RANK_TIERS[$currentIndex];
        $next = self::RANK_TIERS[$currentIndex + 1] ?? null;

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

    public static function badges(User $user, Collection $completedEnrollments, int $streak, bool $boardCleared): array
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
            ['key' => 'on_a_roll', 'icon' => 'flame', 'label' => 'On a Roll', 'description' => 'Reach a streak of 3 consecutive passing grades.', 'unlocked' => $streak >= 3],
            ['key' => 'veteran', 'icon' => 'medal', 'label' => 'Veteran Investigator', 'description' => 'Complete 5 cases.', 'unlocked' => $completedEnrollments->count() >= 5],
            ['key' => 'board_cleared', 'icon' => 'check-check', 'label' => 'Board Cleared', 'description' => 'Complete every case currently assigned to you.', 'unlocked' => $boardCleared],
        ];
    }

    /** Convenience: everything a page needs, computed fresh for this user. */
    public static function forUser(User $user): array
    {
        $hasActiveCase = $user->currentEnrollment() !== null;
        $completedEnrollments = self::completedEnrollments($user);
        $xp = self::xp($completedEnrollments);
        $streak = self::streak($user);
        $boardCleared = self::boardCleared($user, $completedEnrollments, $hasActiveCase);

        return [
            'rank' => self::rank($xp),
            'badges' => self::badges($user, $completedEnrollments, $streak, $boardCleared),
            'xp' => $xp,
            'streak' => $streak,
            'completedEnrollments' => $completedEnrollments,
        ];
    }
}
