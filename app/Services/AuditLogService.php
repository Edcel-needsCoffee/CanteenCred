<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Enums\AuditAction;

class AuditLogService
{
    /**
     * Log an audit entry.
     */
    public function log(
        AuditAction $action,
        string $subjectType,
        int $subjectId,
        string $description,
        ?User $user = null,
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action->value,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a login attempt.
     */
    public function logLoginAttempt(string $username, bool $success, ?User $user = null, ?string $ip = null): AuditLog
    {
        return $this->log(
            $success ? AuditAction::LOGIN_SUCCESS : AuditAction::LOGIN_FAILED,
            'User',
            $user?->id ?? 0,
            $success ? "User {$username} logged in successfully" : "Failed login attempt for username: {$username}",
            $user,
            ['username' => $username, 'ip' => $ip ?? request()->ip()]
        );
    }

    /**
     * Log a logout.
     */
    public function logLogout(User $user): AuditLog
    {
        return $this->log(
            AuditAction::LOGOUT,
            'User',
            $user->id,
            "User {$user->name} logged out",
            $user
        );
    }

    /**
     * Get audit logs with filters.
     */
    public function getLogs(array $filters = [], int $perPage = 25)
    {
        $query = AuditLog::with('user')->latest();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }
}