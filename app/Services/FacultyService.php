<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\User;
use App\Models\AuditLog;
use App\Enums\AuditAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FacultyService
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Create a new faculty account with optional linked user.
     */
    public function createFaculty(array $data, ?User $createdBy = null): Faculty
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $faculty = Faculty::create([
                'faculty_number' => $data['faculty_number'],
                'name' => $data['name'],
                'department' => $data['department'],
                'credit_limit' => $data['credit_limit'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create linked user if requested
            if (!empty($data['create_user']) && !empty($data['username']) && !empty($data['password'])) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? $data['username'] . '@faculty.local',
                    'username' => $data['username'],
                    'password' => Hash::make($data['password']),
                    'role' => User::ROLE_FACULTY,
                    'faculty_id' => $faculty->id,
                    'is_active' => true,
                ]);

                $faculty->user_id = $user->id;
                $faculty->save();
            }

            if ($createdBy) {
                $this->auditLogService->log(
                    AuditAction::FACULTY_CREATED,
                    Faculty::class,
                    $faculty->id,
                    "Faculty account created: {$faculty->name} ({$faculty->faculty_number})",
                    $createdBy,
                    ['faculty_number' => $faculty->faculty_number, 'credit_limit' => $faculty->credit_limit]
                );
            }

            return $faculty;
        });
    }

    /**
     * Update a faculty account.
     */
    public function updateFaculty(Faculty $faculty, array $data, ?User $updatedBy = null): Faculty
    {
        $oldData = $faculty->only(['name', 'department', 'credit_limit', 'is_active']);

        $faculty->update($data);

        if ($updatedBy && $oldData !== $faculty->only(['name', 'department', 'credit_limit', 'is_active'])) {
            $changes = [];

            foreach (['name', 'department', 'credit_limit', 'is_active'] as $field) {
                if ($oldData[$field] !== $faculty->$field) {
                    $changes[$field] = ['old' => $oldData[$field], 'new' => $faculty->$field];
                }
            }

            if (!empty($changes)) {
                $this->auditLogService->log(
                    $changes['credit_limit'] ?? null ? AuditAction::FACULTY_CREDIT_LIMIT_CHANGED : AuditAction::FACULTY_UPDATED,
                    Faculty::class,
                    $faculty->id,
                    "Faculty account updated: {$faculty->name} ({$faculty->faculty_number})",
                    $updatedBy,
                    ['changes' => $changes]
                );
            }
        }

        return $faculty->fresh();
    }

    /**
     * Change faculty credit limit.
     */
    public function changeCreditLimit(Faculty $faculty, float $newLimit, User $changedBy): Faculty
    {
        $oldLimit = $faculty->credit_limit;

        $faculty->update(['credit_limit' => $newLimit]);

        $this->auditLogService->log(
            AuditAction::FACULTY_CREDIT_LIMIT_CHANGED,
            Faculty::class,
            $faculty->id,
            "Credit limit changed for {$faculty->name} ({$faculty->faculty_number}) from {$oldLimit} to {$newLimit}",
            $changedBy,
            ['old_limit' => $oldLimit, 'new_limit' => $newLimit]
        );

        return $faculty->fresh();
    }

    /**
     * Toggle faculty active status.
     */
    public function toggleStatus(Faculty $faculty, User $changedBy): Faculty
    {
        $newStatus = !$faculty->is_active;
        $faculty->update(['is_active' => $newStatus]);

        $this->auditLogService->log(
            AuditAction::FACULTY_STATUS_CHANGED,
            Faculty::class,
            $faculty->id,
            "Faculty status changed to " . ($newStatus ? 'active' : 'inactive') . ": {$faculty->name} ({$faculty->faculty_number})",
            $changedBy,
            ['is_active' => $newStatus]
        );

        return $faculty->fresh();
    }

    /**
     * Search faculty for point-of-sale lookup.
     */
    public function searchFaculty(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return Faculty::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('faculty_number', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get();
    }
}