<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            ActivityLog::create([
                'user_id' => $user?->id ?? 0,
                'action' => 'LOGIN_FAILED',
                'description' => "Failed login attempt for {$request->email}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        Auth::login($user, $request->boolean('remember'));
        ActivityLog::record('LOGIN_SUCCESS', 'User logged in successfully');

        return redirect()->intended($this->redirectPath($user->role));
    }

    public function showRegister() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:student,lecturer',
            'student_id' => 'nullable|string|unique:users',
            'staff_id' => 'nullable|string|unique:users',
            'faculty' => 'nullable|string|max:255',
            'program' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'student_id' => $request->student_id,
            'staff_id' => $request->staff_id,
            'faculty' => $request->faculty,
            'program' => $request->program,
        ]);

        Auth::login($user);
        ActivityLog::record('REGISTER', 'New account registered');

        return redirect($this->redirectPath($user->role));
    }

    public function logout(Request $request) {
        ActivityLog::record('LOGOUT', 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectPath(string $role): string {
        return match($role) {
            'lecturer' => '/lecturer/dashboard',
            default => '/student/dashboard',
        };
    }
}
