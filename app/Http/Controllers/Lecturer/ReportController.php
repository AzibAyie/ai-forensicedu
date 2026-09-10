<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseEnrollment;
use App\Models\CaseReport;
use App\Models\ForensicCase;
use App\Services\AIService;
use App\Services\IntegrityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(ForensicCase $forensicCase)
    {
        if ($forensicCase->lecturer_id !== auth()->id()) abort(403);

        $enrollments = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->with(['student', 'report'])
            ->latest()->get();

        return view('lecturer.report.index', compact('forensicCase', 'enrollments'));
    }

    public function show(ForensicCase $forensicCase, CaseEnrollment $enrollment)
    {
        if ($forensicCase->lecturer_id !== auth()->id()) abort(403);

        $report = $enrollment->report;
        $answers = $enrollment->answers()->with('question')->get();
        $student = $enrollment->student;

        $integrity = $report ? app(IntegrityService::class)->analyse($report) : null;

        return view('lecturer.report.show', compact(
            'forensicCase', 'enrollment', 'report', 'answers', 'student', 'integrity'
        ));
    }

    public function grade(Request $request, ForensicCase $forensicCase, CaseEnrollment $enrollment)
    {
        if ($forensicCase->lecturer_id !== auth()->id()) abort(403);

        $request->validate([
            'marks' => "required|integer|min:0|max:{$forensicCase->total_marks}",
            'lecturer_feedback' => 'required|string',
        ]);

        $enrollment->report->update([
            'marks' => $request->marks,
            'lecturer_feedback' => $request->lecturer_feedback,
            'status' => 'graded',
        ]);

        $enrollment->update(['status' => 'graded']);

        ActivityLog::record('REPORT_GRADED', "Graded report for student #{$enrollment->student_id}", $forensicCase->id);

        return back()->with('success', 'Report graded successfully.');
    }

    public function aiEvaluate(ForensicCase $forensicCase, CaseEnrollment $enrollment)
    {
        if ($forensicCase->lecturer_id !== auth()->id()) abort(403);

        $report = $enrollment->report;
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'No report submitted yet.'], 404);
        }

        $questions = $forensicCase->questions->map(fn($q) => [
            'id' => $q->id, 'question' => $q->question, 'marks' => $q->marks,
        ])->toArray();

        $answers = $enrollment->answers()->with('question')->get()
            ->mapWithKeys(fn($a) => [$a->question_id => $a->answer])->toArray();

        $ai = new AIService();
        $evaluation = $ai->evaluateReport([
            'executive_summary' => $report->executive_summary,
            'findings' => $report->findings,
            'timeline_reconstruction' => $report->timeline_reconstruction,
            'recommendations' => $report->recommendations,
            'conclusion' => $report->conclusion,
            'answers' => $answers,
        ], $questions, $forensicCase->scenario);

        if (empty($evaluation)) {
            return response()->json(['success' => false, 'message' => 'AI evaluation failed.'], 422);
        }

        $report->update([
            'ai_feedback' => $evaluation['detailed_feedback'] ?? '',
            'ai_suggested_marks' => $evaluation['overall_score'] ?? null,
        ]);

        return response()->json(['success' => true, 'evaluation' => $evaluation]);
    }

    public function downloadAnswer(ForensicCase $forensicCase, CaseEnrollment $enrollment)
    {
        if ($forensicCase->lecturer_id !== auth()->id()) abort(403);
        $report = $enrollment->report;
        abort_unless($report && $report->answer_pdf_path, 404);

        return Storage::disk('local')->download(
            $report->answer_pdf_path,
            $report->answer_pdf_name ?? 'answer.pdf'
        );
    }

    public function exportPdf(ForensicCase $forensicCase, CaseEnrollment $enrollment)
    {
        if ($forensicCase->lecturer_id !== auth()->id()) abort(403);
        $report = $enrollment->report;
        $student = $enrollment->student;
        $answers = $enrollment->answers()->with('question')->get();

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('lecturer.report.pdf', compact('forensicCase', 'report', 'student', 'answers', 'enrollment'));

        $studentSlug = Str::slug($student->name) ?: 'student';
        $caseSlug = Str::slug($forensicCase->title) ?: 'case';

        return $pdf->download("{$studentSlug}_{$caseSlug}_report.pdf");
    }
}
