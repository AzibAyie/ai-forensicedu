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
            ->with('forensicCase')
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
            ->whereHas('report', fn($q) => $q->whereNotNull('marks'))
            ->with('report', 'forensicCase')
            ->get()
            ->sortByDesc(fn($e) => $e->submitted_at)
            ->values();

        $streak = 0;
        foreach ($gradedForStreak as $e) {
            $pct = ($e->report->marks / max($e->forensicCase->total_marks, 1)) * 100;
            if ($pct < 50) break;
            $streak++;
        }

        $stats = [
            'total_cases' => $user->enrollments()->count(),
            'completed' => $completedEnrollments->count(),
            'in_progress' => $hasActiveCase ? 1 : 0,
            'streak' => $streak,
            'avg_score' => $user->enrollments()
                ->whereHas('report', fn($q) => $q->whereNotNull('marks'))
                ->with('report')
                ->get()
                ->avg(fn($e) => $e->report?->marks) ?? 0,
        ];

        $recentActivity = ActivityLog::where('user_id', $user->id)
            ->latest()->limit(5)->get();

        return view('student.dashboard.index', compact(
            'currentEnrollment', 'hasActiveCase',
            'availableCases', 'completedEnrollments',
            'stats', 'recentActivity'
        ));
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
            if (!$forensicCase->checkPassword($request->password)) {
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
