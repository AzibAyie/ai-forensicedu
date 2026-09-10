<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * "View as student" — lets a lecturer see the platform exactly as one of their
 * students sees it. The original identity is held in the session so the
 * lecturer can return with one click, and both directions are written to the
 * activity log so impersonation is never silent.
 */
class ImpersonationController extends Controller
{
    public function start(User $student)
    {
        // A lecturer may view as any student in the system.
        abort_unless($student->role === 'student', 404);

        ActivityLog::record('IMPERSONATION_START', "Viewing as {$student->name}");

        session(['impersonator_id' => auth()->id()]);
        Auth::login($student);

        return redirect()->route('student.dashboard')
            ->with('success', "You are now viewing as {$student->name}.");
    }

    /**
     * Switch directly from one student's view to another while already
     * impersonating, without exiting first. Reachable regardless of the
     * currently-authenticated (student) role, so the guard here is the
     * session flag itself — only start() ever sets it, and only for a
     * genuine lecturer, so a plain student has no way to forge it.
     */
    public function switchTo(User $student)
    {
        $lecturer = User::where('id', session('impersonator_id'))->where('role', 'lecturer')->first();
        abort_unless($lecturer, 403, 'You are not currently viewing as a student.');
        abort_unless($student->role === 'student', 404);

        ActivityLog::record('IMPERSONATION_SWITCH', "Switched view to {$student->name}", null, [], $lecturer->id);

        Auth::login($student);

        return redirect()->route('student.dashboard')
            ->with('success', "Now viewing as {$student->name}.");
    }

    /**
     * All students, for the in-banner quick-switch search. Only reachable
     * while impersonating (same guard as switchTo).
     */
    public function pickerData()
    {
        $lecturer = User::where('id', session('impersonator_id'))->where('role', 'lecturer')->first();
        abort_unless($lecturer, 403);

        $students = User::where('role', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'student_id', 'email'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'identifier' => $s->student_id ?? $s->email,
            ]);

        return response()->json($students);
    }

    public function stop()
    {
        $originalId = session('impersonator_id');
        if (! $originalId) {
            return redirect()->route('student.dashboard');
        }

        $lecturer = User::find($originalId);
        session()->forget('impersonator_id');

        if (! $lecturer) {
            Auth::logout();
            return redirect()->route('login');
        }

        Auth::login($lecturer);
        ActivityLog::record('IMPERSONATION_STOP', 'Returned to lecturer account');

        return redirect()->route('lecturer.students')->with('success', 'Back in your own account.');
    }
}
