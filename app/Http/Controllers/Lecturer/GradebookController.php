<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CaseEnrollment;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradebookController extends Controller
{
    public function index(Request $request)
    {
        $cases = ForensicCase::where('lecturer_id', auth()->id())
            ->orderBy('id')
            ->get();

        $caseIds = $cases->pluck('id');

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $query = User::where('role', 'student')
            ->whereHas('enrollments', fn ($q) => $q->whereIn('forensic_case_id', $caseIds));

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $enrollments = CaseEnrollment::whereIn('forensic_case_id', $caseIds)
            ->whereIn('student_id', $students->pluck('id'))
            ->with('report')
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function ($student) use ($cases, $enrollments) {
            $mine = $enrollments->get($student->id, collect())->keyBy('forensic_case_id');

            $cells = [];
            $earned = 0;
            $available = 0;
            $percentages = [];
            $submittedCount = 0;

            foreach ($cases as $case) {
                $e = $mine->get($case->id);

                if (! $e) {
                    $cells[$case->id] = ['state' => 'not_enrolled'];
                    continue;
                }

                $marks = $e->report?->marks;
                $isSubmitted = in_array($e->status, ['submitted', 'graded']);
                if ($isSubmitted) {
                    $submittedCount++;
                }

                if ($marks !== null) {
                    $pct = round($marks / max($case->total_marks, 1) * 100);
                    $earned += $marks;
                    $available += $case->total_marks;
                    $percentages[] = $pct;

                    $cells[$case->id] = [
                        'state' => 'graded',
                        'marks' => $marks,
                        'total' => $case->total_marks,
                        'pct' => $pct,
                        'date' => $e->submitted_at,
                        'enrollment' => $e,
                    ];
                } elseif ($isSubmitted) {
                    $cells[$case->id] = [
                        'state' => 'submitted',
                        'total' => $case->total_marks,
                        'date' => $e->submitted_at,
                        'enrollment' => $e,
                    ];
                } else {
                    $cells[$case->id] = [
                        'state' => 'in_progress',
                        'progress' => $e->progress_percent,
                        'enrollment' => $e,
                    ];
                }
            }

            $enrolledCount = $mine->count();

            return [
                'student' => $student,
                'cells' => $cells,
                'class_grade' => $available > 0 ? round($earned / $available * 100, 1) : null,
                'average' => count($percentages) ? round(array_sum($percentages) / count($percentages)) : null,
                'submitted' => $submittedCount,
                'enrolled' => $enrolledCount,
                'complete' => $enrolledCount > 0 && $submittedCount === $enrolledCount,
            ];
        });

        // Per-case column footers
        $caseStats = [];
        foreach ($cases as $case) {
            $scores = collect($rows)
                ->map(fn ($r) => $r['cells'][$case->id]['pct'] ?? null)
                ->filter(fn ($v) => $v !== null);

            $caseStats[$case->id] = [
                'avg' => $scores->count() ? round($scores->avg()) : null,
                'graded' => $scores->count(),
            ];
        }

        return view('lecturer.gradebook.index', compact(
            'cases', 'rows', 'students', 'caseStats', 'perPage'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $cases = ForensicCase::where('lecturer_id', auth()->id())->orderBy('id')->get();
        $caseIds = $cases->pluck('id');

        $students = User::where('role', 'student')
            ->whereHas('enrollments', fn ($q) => $q->whereIn('forensic_case_id', $caseIds))
            ->orderBy('name')->get();

        $enrollments = CaseEnrollment::whereIn('forensic_case_id', $caseIds)
            ->with('report')->get()->groupBy('student_id');

        $filename = 'gradebook-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($cases, $students, $enrollments) {
            $out = fopen('php://output', 'w');

            $header = ['Student ID', 'Name', 'Email', 'Programme'];
            foreach ($cases as $c) {
                $header[] = 'CASE-' . str_pad($c->id, 3, '0', STR_PAD_LEFT) . ' ' . $c->title . ' (/' . $c->total_marks . ')';
            }
            $header[] = 'Class Grade %';
            $header[] = 'Cases Submitted';
            fputcsv($out, $header);

            foreach ($students as $s) {
                $mine = $enrollments->get($s->id, collect())->keyBy('forensic_case_id');
                $row = [$s->student_id, $s->name, $s->email, $s->program];

                $earned = 0; $available = 0; $submitted = 0;
                foreach ($cases as $c) {
                    $e = $mine->get($c->id);
                    if (! $e) { $row[] = ''; continue; }
                    if (in_array($e->status, ['submitted', 'graded'])) { $submitted++; }

                    $marks = $e->report?->marks;
                    if ($marks !== null) {
                        $row[] = $marks;
                        $earned += $marks;
                        $available += $c->total_marks;
                    } else {
                        $row[] = in_array($e->status, ['submitted', 'graded']) ? 'ungraded' : 'in progress';
                    }
                }

                $row[] = $available > 0 ? round($earned / $available * 100, 1) : '';
                $row[] = $submitted;
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
