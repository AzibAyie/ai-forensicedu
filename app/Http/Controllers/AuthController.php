<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ClassJoinRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect($this->redirectPath(Auth::user()->role));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($user) {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'LOGIN_FAILED',
                    'description' => "Failed login attempt for {$request->email}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        Auth::login($user, $request->boolean('remember'));
        ActivityLog::record('LOGIN_SUCCESS', 'User logged in successfully');

        return redirect()->intended($this->redirectPath($user->role));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', $this->passwordRules()],
            'role' => 'required|in:student,lecturer',
            'student_id' => 'nullable|string|unique:users',
            'staff_id' => 'nullable|string|unique:users',
            'faculty' => 'nullable|string|max:255',
            'program' => 'nullable|string|max:255',
            'class_code' => 'required_if:role,student|nullable|string',
        ]);

        $lecturer = null;
        if ($request->role === 'student') {
            $lecturer = User::where('role', 'lecturer')
                ->whereRaw('UPPER(class_code) = ?', [strtoupper($request->class_code)])
                ->first();

            if (! $lecturer) {
                return back()->withErrors([
                    'class_code' => 'That class code was not recognised. Check it with your lecturer and try again.',
                ])->withInput();
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'student_id' => $request->student_id,
            'staff_id' => $request->staff_id,
            'faculty' => $request->faculty,
            'program' => $request->program,
            // lecturer_id is intentionally left unset here — a student only gets
            // it once the lecturer approves their join request below.
            'class_code' => $request->role === 'lecturer' ? User::generateClassCode() : null,
        ]);

        Auth::login($user);
        ActivityLog::record('REGISTER', 'New account registered');

        if ($lecturer) {
            ClassJoinRequest::create([
                'student_id' => $user->id,
                'lecturer_id' => $lecturer->id,
                'status' => 'pending',
            ]);
            ActivityLog::record('CLASS_JOIN_REQUESTED', "Requested to join {$lecturer->name}'s class");
        }

        return redirect($this->redirectPath($user->role))
            ->with('success', $lecturer
                ? "Account created. Your request to join {$lecturer->name}'s class is now waiting for their approval."
                : 'Account created.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // TEMPORARY diagnostic logging — the user-facing message stays the
        // same regardless (still an anti-enumeration measure), but we log
        // what Laravel actually did server-side, since that's invisible
        // from outside while debugging why Resend never receives anything.
        try {
            $status = Password::sendResetLink($request->only('email'));
            Log::info('PASSWORD_RESET_DEBUG: broker returned', [
                'status' => $status,
                'mailer' => config('mail.default'),
                'resend_key_present' => filled(config('services.resend.key')),
                'from_address' => config('mail.from.address'),
            ]);
        } catch (\Throwable $e) {
            Log::error('PASSWORD_RESET_DEBUG: threw', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }

        // Deliberately the same message whether or not the email exists,
        // so the form can't be used to check which addresses are registered.
        return back()->with('success', 'If that email is registered, a password reset link has been sent to it.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', $this->passwordRules()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                ActivityLog::record('PASSWORD_RESET', 'Password reset via email link', null, [], $user->id);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Your password has been reset. You can now sign in.')
            : back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('LOGOUT', 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectPath(string $role): string
    {
        return match ($role) {
            'lecturer' => '/lecturer/dashboard',
            default => '/student/dashboard',
        };
    }

    /**
     * Applies to every newly-set password (registration and reset) — never
     * re-checked against passwords already stored, so the seeded demo
     * accounts (lecturer@forensicedu.test / student@forensicedu.test,
     * password "password") keep working untouched.
     */
    private function passwordRules(): PasswordRule
    {
        return PasswordRule::min(8)->mixedCase()->numbers()->symbols();
    }
}
