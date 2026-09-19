<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ForensicCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $cases = ForensicCase::where('lecturer_id', $user->id)
            ->withCount([
                'enrollments',
                'enrollments as submitted_count' => fn ($q) => $q->whereIn('status', ['submitted', 'graded']),
                'enrollments as awaiting_count' => fn ($q) => $q->where('status', 'submitted'),
            ])
            ->latest()->get();

        $stats = [
            'total_cases' => $cases->count(),
            'published' => $cases->where('is_published', true)->count(),
            'total_students' => $cases->sum('enrollments_count'),
            'pending_reviews' => $cases->sum('awaiting_count'),
        ];

        return view('lecturer.dashboard.index', compact('cases', 'stats', 'user'));
    }

    public function profile()
    {
        $user = auth()->user();

        // Backfill a class code for lecturer accounts created before this feature existed.
        if (! $user->class_code) {
            $user->update(['class_code' => User::generateClassCode()]);
        }

        return view('lecturer.profile', ['user' => $user]);
    }

    public function regenerateClassCode()
    {
        $user = auth()->user();
        $user->update(['class_code' => User::generateClassCode()]);

        return back()->with('success', 'New class code generated. Students using the old code will need the new one to join.');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'faculty' => 'nullable|string|max:255',
        ]);
        $user->update($request->only('name', 'phone', 'faculty'));

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

    public function students()
    {
        $user = auth()->user();
        $caseIds = ForensicCase::where('lecturer_id', $user->id)->pluck('id');

        // Students formally assigned to this lecturer.
        $students = User::where('role', 'student')
            ->where('lecturer_id', $user->id)
            ->with(['enrollments' => fn ($q) => $q->whereIn('forensic_case_id', $caseIds)->with('forensicCase', 'report')])
            ->orderBy('name')->get();

        // Students working on this lecturer's cases but not yet assigned to anyone.
        $unassigned = User::where('role', 'student')
            ->whereNull('lecturer_id')
            ->whereHas('enrollments', fn ($q) => $q->whereIn('forensic_case_id', $caseIds))
            ->orderBy('name')->get();

        // Everyone else — a lecturer can view as any student, not just their own.
        $otherStudents = User::where('role', 'student')
            ->whereNotIn('id', $students->pluck('id')->merge($unassigned->pluck('id')))
            ->with('lecturer')
            ->orderBy('name')->get();

        return view('lecturer.students.index', compact('students', 'unassigned', 'otherStudents'));
    }

    public function assignStudent(Request $request, User $student)
    {
        abort_unless($student->role === 'student', 403);
        $student->update(['lecturer_id' => auth()->id()]);

        return back()->with('success', "{$student->name} is now assigned to you.");
    }

    public function unassignStudent(User $student)
    {
        abort_unless($student->lecturer_id === auth()->id(), 403);
        $student->update(['lecturer_id' => null]);

        return back()->with('success', "{$student->name} is no longer assigned to you.");
    }
}
