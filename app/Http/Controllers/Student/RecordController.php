<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ForensicCase;

class RecordController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $graded = $user->enrollments()
            ->with(['forensicCase', 'report'])
            ->where('status', 'graded')
            ->whereHas('report', fn ($q) => $q->whereNotNull('marks'))
            ->orderBy('submitted_at')
            ->get();

        // Score history for the chart — normalised to a percentage so cases
        // with different total marks stay comparable.
        $scoreHistory = $graded->map(fn ($e) => [
            'label' => 'C' . str_pad($e->forensicCase->id, 3, '0', STR_PAD_LEFT),
            'score' => round(($e->report->marks / max($e->forensicCase->total_marks, 1)) * 100),
        ])->values()->all();

        $scores = array_column($scoreHistory, 'score');

        $stats = [
            'completed' => $user->enrollments()->whereIn('status', ['submitted', 'graded'])->count(),
            'avg_score' => count($scores) ? array_sum($scores) / count($scores) : 0,
            'best_score' => count($scores) ? max($scores) : null,
            'total_actions' => ActivityLog::where('user_id', $user->id)->count(),
        ];

        // Coverage across the three incident types
        $types = [
            'brute_force' => 'Brute force',
            'unauthorized_modification' => 'Data modification',
            'mass_deletion' => 'Mass deletion',
        ];

        $competency = [];
        foreach ($types as $key => $label) {
            $total = ForensicCase::where('incident_type', $key)->where('is_published', true)->count();

            $done = $user->enrollments()
                ->whereIn('status', ['submitted', 'graded'])
                ->whereHas('forensicCase', fn ($q) => $q->where('incident_type', $key))
                ->count();

            $typeScores = $graded
                ->filter(fn ($e) => $e->forensicCase->incident_type === $key)
                ->map(fn ($e) => ($e->report->marks / max($e->forensicCase->total_marks, 1)) * 100);

            $competency[$key] = [
                'label' => $label,
                'total' => $total,
                'done' => $done,
                'avg' => $typeScores->count() ? $typeScores->avg() : null,
            ];
        }

        $milestones = $this->buildMilestones($user, $stats, $competency, $scores);

        // Celebrate a milestone once per browser session, the first time it's seen earned.
        $celebrated = session('celebrated_milestones', []);
        $newlyEarned = collect($milestones)
            ->filter(fn ($m) => $m['earned'] && !in_array($m['mark'], $celebrated))
            ->values()->all();
        session(['celebrated_milestones' => array_unique(array_merge(
            $celebrated, collect($milestones)->where('earned', true)->pluck('mark')->all()
        ))]);

        $recentActivity = ActivityLog::where('user_id', $user->id)
            ->latest()->limit(12)->get();

        return view('student.record', compact(
            'user', 'stats', 'scoreHistory', 'competency', 'milestones', 'recentActivity', 'newlyEarned'
        ));
    }

    private function buildMilestones($user, array $stats, array $competency, array $scores): array
    {
        $completed = $stats['completed'];
        $typesCovered = collect($competency)->filter(fn ($c) => $c['done'] > 0)->count();
        $highScores = collect($scores)->filter(fn ($s) => $s >= 80)->count();
        $evidenceViews = ActivityLog::where('user_id', $user->id)
            ->where('action', 'EVIDENCE_OPENED')->count();

        return [
            [
                'mark' => 'I',
                'name' => 'First Case Closed',
                'desc' => 'File your first forensic report.',
                'earned' => $completed >= 1,
                'progress' => $completed . '/1',
            ],
            [
                'mark' => 'III',
                'name' => 'Case Load',
                'desc' => 'Close three separate investigations.',
                'earned' => $completed >= 3,
                'progress' => min($completed, 3) . '/3',
            ],
            [
                'mark' => '△',
                'name' => 'Full Spectrum',
                'desc' => 'Investigate all three incident types.',
                'earned' => $typesCovered >= 3,
                'progress' => $typesCovered . '/3',
            ],
            [
                'mark' => '◈',
                'name' => 'Distinction',
                'desc' => 'Score 80% or higher on a report.',
                'earned' => $highScores >= 1,
                'progress' => $highScores . '/1',
            ],
            [
                'mark' => '⌕',
                'name' => 'Thorough',
                'desc' => 'Open 20 evidence panels across cases.',
                'earned' => $evidenceViews >= 20,
                'progress' => min($evidenceViews, 20) . '/20',
            ],
        ];
    }
}
