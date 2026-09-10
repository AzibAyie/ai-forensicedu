<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseEnrollment;
use App\Models\CaseReport;
use App\Models\ForensicCase;
use App\Services\IntegrityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function show(ForensicCase $forensicCase)
    {
        $user = auth()->user();
        $enrollment = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', $user->id)
            ->firstOrFail();

        $report = $enrollment->report;
        $answers = $enrollment->answers()->with('question')->get();

        return view('student.report.show', compact('forensicCase', 'enrollment', 'report', 'answers'));
    }

    public function store(Request $request, ForensicCase $forensicCase, IntegrityService $integrity)
    {
        $user = auth()->user();
        $enrollment = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $request->validate([
            'student_name' => 'required|string|max:255',
            'student_id_number' => 'required|string|max:50',
            'program' => 'required|string|max:255',
            'findings' => 'required|string|min:100',
            'answer_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ], [
            'answer_pdf.mimes' => 'The answer sheet must be a PDF file.',
            'answer_pdf.max' => 'The answer sheet must be under 10 MB.',
        ]);

        // A case with investigation questions must have all of them answered
        // before the report can be filed — this used to be a soft warning the
        // student could click past, which let reports through with the
        // per-question work skipped entirely. Enforced server-side so it
        // can't be bypassed by submitting the form directly either.
        $totalQuestions = $forensicCase->questions()->count();
        $answeredQuestions = $enrollment->answers()->count();
        if ($totalQuestions > 0 && $answeredQuestions < $totalQuestions) {
            return back()->withInput()->withErrors([
                'findings' => "You've only answered {$answeredQuestions} of {$totalQuestions} investigation questions. Go back to the case page and answer the rest before submitting your report.",
            ]);
        }

        $report = CaseReport::firstOrNew(['enrollment_id' => $enrollment->id]);

        $report->fill($request->only([
            'student_name', 'student_id_number', 'program', 'findings',
        ]));

        if ($request->hasFile('answer_pdf')) {
            if ($report->answer_pdf_path) {
                Storage::disk('local')->delete($report->answer_pdf_path);
            }
            $file = $request->file('answer_pdf');
            $report->answer_pdf_path = $file->store("answers/{$forensicCase->id}", 'local');
            $report->answer_pdf_name = $file->getClientOriginalName();
        }

        $this->applyTelemetry($report, $request);
        $report->status = 'submitted';
        $report->save();

        $enrollment->update([
            'status' => 'submitted',
            'progress_percent' => 100,
            'submitted_at' => now(),
        ]);

        $integrity->store($report->fresh('enrollment'));

        ActivityLog::record('REPORT_SUBMITTED', "Submitted report for: {$forensicCase->title}", $forensicCase->id);

        return redirect()->route('student.dashboard')
            ->with('success', 'Report submitted. Your lecturer will review it shortly.');
    }

    public function saveDraft(Request $request, ForensicCase $forensicCase)
    {
        $user = auth()->user();
        $enrollment = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', $user->id)->firstOrFail();

        if ($enrollment->isSubmitted()) {
            return response()->json(['success' => false, 'message' => 'Report already submitted.'], 422);
        }

        $report = CaseReport::firstOrNew(['enrollment_id' => $enrollment->id]);
        $report->fill($request->only([
            'student_name', 'student_id_number', 'program', 'findings',
        ]));

        $this->applyTelemetry($report, $request);
        $report->revision_count = ($report->revision_count ?? 0) + 1;
        $report->status = 'draft';
        $report->save();

        return response()->json(['success' => true, 'revisions' => $report->revision_count]);
    }

    /**
     * Record how the text was produced. Client-reported and therefore not
     * tamper-proof — shown to the marker as context, never used to block a
     * submission or decide misconduct on its own.
     */
    private function applyTelemetry(CaseReport $report, Request $request): void
    {
        $report->keystroke_count = max((int) $request->input('_keystrokes', 0), (int) ($report->keystroke_count ?? 0));
        $report->compose_seconds = max((int) $request->input('_compose_seconds', 0), (int) ($report->compose_seconds ?? 0));

        $events = json_decode((string) $request->input('_paste_events', '[]'), true);
        if (is_array($events) && $events) {
            $existing = $report->paste_events ?? [];
            $merged = array_slice(array_merge($existing, $events), -50);
            $report->paste_events = $merged;
            $report->paste_count = count($merged);
            $report->pasted_chars = array_sum(array_column($merged, 'length'));
        }
    }

    public function downloadQuestions(ForensicCase $forensicCase)
    {
        $enrolled = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', auth()->id())->exists();

        abort_unless($enrolled && $forensicCase->question_pdf_path, 404);

        ActivityLog::record('QUESTIONS_DOWNLOADED', 'Downloaded question sheet', $forensicCase->id);

        return Storage::disk('local')->download(
            $forensicCase->question_pdf_path,
            $forensicCase->question_pdf_name ?? 'questions.pdf'
        );
    }
}
