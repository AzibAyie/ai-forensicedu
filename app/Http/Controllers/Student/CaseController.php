<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseAnswer;
use App\Models\CaseEnrollment;
use App\Models\ForensicCase;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function show(ForensicCase $forensicCase)
    {
        $user = auth()->user();
        $enrollment = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', $user->id)
            ->firstOrFail();

        $questions = $forensicCase->questions;
        $answers = CaseAnswer::where('enrollment_id', $enrollment->id)
            ->pluck('answer', 'question_id');

        $activityLogs = ActivityLog::where('user_id', $user->id)
            ->where('forensic_case_id', $forensicCase->id)
            ->latest()->get();

        ActivityLog::record('EVIDENCE_VIEWED', "Viewed case evidence for: {$forensicCase->title}", $forensicCase->id);

        return view('student.case.show', compact(
            'forensicCase', 'enrollment', 'questions', 'answers', 'activityLogs'
        ));
    }

    public function saveAnswer(Request $request, ForensicCase $forensicCase)
    {
        $user = auth()->user();
        $enrollment = CaseEnrollment::where('forensic_case_id', $forensicCase->id)
            ->where('student_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $request->validate([
            'question_id' => 'required|exists:case_questions,id',
            'answer' => 'required|string',
            'external_paste' => 'sometimes|boolean',
        ]);

        $answer = CaseAnswer::firstOrNew(['enrollment_id' => $enrollment->id, 'question_id' => $request->question_id]);
        $answer->answer = $request->answer;
        // Sticky flag — once a rich/external paste is detected for this
        // question, it stays flagged even if the student edits the text
        // afterwards. See IntegrityService for why this signal is used.
        if ($request->boolean('external_paste')) {
            $answer->flagged_external_paste = true;
        }
        $answer->save();

        $totalQuestions = $forensicCase->questions()->count();
        $answeredQuestions = CaseAnswer::where('enrollment_id', $enrollment->id)->count();
        $progress = $totalQuestions > 0 ? min(90, intval(($answeredQuestions / $totalQuestions) * 85) + 5) : 5;

        $enrollment->update(['progress_percent' => $progress, 'status' => 'in_progress']);

        ActivityLog::record('ANSWER_SAVED', "Saved answer for question #{$request->question_id}", $forensicCase->id);

        return response()->json(['success' => true, 'progress' => $progress]);
    }

    public function logActivity(Request $request, ForensicCase $forensicCase)
    {
        $request->validate(['action' => 'required|string', 'description' => 'nullable|string']);
        ActivityLog::record($request->action, $request->description ?? '', $forensicCase->id);
        return response()->json(['success' => true]);
    }
}
