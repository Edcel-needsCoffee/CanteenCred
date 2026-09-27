<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Use web guard for all routes (session-based auth)
        $guard = 'web';
        
        if (!Auth::guard($guard)->check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Unauthenticated.',
                    ]
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::guard($guard)->user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::guard($guard)->logout();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'ACCOUNT_DEACTIVATED',
                        'message' => 'Your account has been deactivated.',
                    ]
                ], 403);
            }
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Check if account is locked
        if ($user->locked_until && $user->locked_until->isFuture()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'ACCOUNT_LOCKED',
                        'message' => 'Your account is temporarily locked.',
                    ]
                ], 403);
            }
            return redirect()->route('login')->with('error', 'Your account is temporarily locked.');
        }

        // Check role - accept comma-separated roles
        $allowedRoles = collect($roles)->flatMap(fn($role) => explode(',', $role));
        
        $userRole = $user->role instanceof \App\Enums\UserRole 
            ? $user->role->value 
            : $user->role;
        
        if (!$allowedRoles->contains($userRole)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'You do not have permission to access this resource.',
                    ]
                ], 403);
            }
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}