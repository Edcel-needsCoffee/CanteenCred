<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AuditLogService;
use App\Enums\AuditAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        return view('auth.login');
    }

    /**
     * Handle login attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $maxAttempts = config('app.facu_cred_login_max_attempts', 5);
        $lockoutMinutes = config('app.facu_cred_login_lockout_minutes', 15);
        $key = 'login:' . $username . ':' . $request->ip();

        // Check rate limiting
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $this->auditLogService->logLoginAttempt($username, false, null, $request->ip());
            
            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Find user by username
        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, $lockoutMinutes * 60);
            $this->auditLogService->logLoginAttempt($username, false, $user, $request->ip());

            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            $this->auditLogService->logLoginAttempt($username, false, $user, $request->ip());
            
            throw ValidationException::withMessages([
                'username' => 'Your account has been deactivated. Please contact an administrator.',
            ]);
        }

        // Check if account is locked
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $this->auditLogService->logLoginAttempt($username, false, $user, $request->ip());
            
            throw ValidationException::withMessages([
                'username' => 'Your account is temporarily locked. Please try again later.',
            ]);
        }

        // Reset failed attempts on successful login
        RateLimiter::clear($key);
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Log successful login
        $this->auditLogService->logLoginAttempt($username, true, $user, $request->ip());

        // Log in the user
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->redirectToDashboard())->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLogService->logLogout($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Redirect user to appropriate dashboard based on role.
     */
    protected function redirectToDashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return route('login');
        }

        return match ($user->role) {
            UserRole::ADMINISTRATOR->value => route('admin.dashboard'),
            UserRole::CASHIER->value => route('cashier.pos'),
            UserRole::PAYROLL->value => route('payroll.dashboard'),
            UserRole::FACULTY->value => route('faculty.dashboard'),
            default => route('login'),
        };
    }
}