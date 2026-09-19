<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CaseEnrollment;
use App\Models\ClassJoinRequest;
use App\Models\ForensicCase;
use App\Models\User;
use App\Support\Achievements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

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

        // Scoped to the student's own assigned lecturer — a student never sees
        // another lecturer's cases, even if that lecturer has published some.
        $availableCases = $user->lecturer_id
            ? ForensicCase::available()
                ->where('lecturer_id', $user->lecturer_id)
                ->whereNotIn('id', $user->enrollments()->pluck('forensic_case_id'))
                ->with('lecturer')
                ->latest()
                ->get()
            : collect();

        $streak = Achievements::streak($user);

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

        $classStatus = $user->classStatus();

        return view('student.dashboard.index', compact(
            'currentEnrollment', 'hasActiveCase',
            'availableCases', 'completedEnrollments',
            'stats', 'recentActivity', 'classStatus'
        ));
    }

    public function unlockCase(Request $request, ForensicCase $forensicCase)
    {
        $user = auth()->user();

        // Belt-and-braces: even though the case board only lists cases from the
        // student's own lecturer, this blocks unlocking one directly by URL too.
        if ($forensicCase->lecturer_id !== $user->lecturer_id) {
            abort(404);
        }

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
        $user = auth()->user();

        return view('student.profile', [
            'user' => $user,
            'classStatus' => $user->classStatus(),
        ]);
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

    public function updatePassword(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();
        ActivityLog::record('PASSWORD_CHANGED', 'Password changed from profile settings');

        return back()->with('success', 'Password updated successfully.');
    }

    public function joinClass(Request $request)
    {
        $request->validate(['class_code' => 'required|string']);

        $user = auth()->user();

        $lecturer = User::where('role', 'lecturer')
            ->whereRaw('UPPER(class_code) = ?', [strtoupper($request->class_code)])
            ->first();

        if (! $lecturer) {
            return back()->withErrors(['class_code' => 'That class code was not recognised. Check it with your lecturer and try again.']);
        }

        if ($user->lecturer_id === $lecturer->id) {
            return back()->withErrors(['class_code' => "You're already enrolled with {$lecturer->name}."]);
        }

        // A student can only have one request outstanding at a time — submitting
        // a new code supersedes whatever was pending before, rather than piling up.
        $user->classJoinRequests()->pending()->delete();

        ClassJoinRequest::create([
            'student_id' => $user->id,
            'lecturer_id' => $lecturer->id,
            'status' => 'pending',
        ]);

        ActivityLog::record('CLASS_JOIN_REQUESTED', "Requested to join {$lecturer->name}'s class");

        return back()->with('success', "Request sent. You'll get access to {$lecturer->name}'s cases once they approve it.");
    }
}
