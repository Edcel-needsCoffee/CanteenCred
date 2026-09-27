<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Enums\AuditAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Login via API.
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

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $this->auditLogService->logLoginAttempt($username, false, null, $request->ip());
            
            return response()->json([
                'error' => [
                    'code' => 'TOO_MANY_ATTEMPTS',
                    'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ]
            ], 429);
        }

        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, $lockoutMinutes * 60);
            $this->auditLogService->logLoginAttempt($username, false, $user, $request->ip());

            return response()->json([
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Invalid username or password.',
                ]
            ], 401);
        }

        if (!$user->is_active) {
            $this->auditLogService->logLoginAttempt($username, false, $user, $request->ip());
            
            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_DEACTIVATED',
                    'message' => 'Your account has been deactivated. Please contact an administrator.',
                ]
            ], 403);
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            $this->auditLogService->logLoginAttempt($username, false, $user, $request->ip());
            
            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_LOCKED',
                    'message' => 'Your account is temporarily locked. Please try again later.',
                ]
            ], 403);
        }

        RateLimiter::clear($key);
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->auditLogService->logLoginAttempt($username, true, $user, $request->ip());

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user->load('faculty'),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Login successful',
        ]);
    }

    /**
     * Logout via API.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $this->auditLogService->logLogout($user);
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json([
            'data' => $request->user()->load('faculty'),
        ]);
    }
}