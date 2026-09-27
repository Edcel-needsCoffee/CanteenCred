<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 
    'email', 
    'username', 
    'password', 
    'role', 
    'faculty_id', 
    'is_active', 
    'failed_login_attempts', 
    'locked_until',
    'remember_token',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Role constants
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_CASHIER = 'cashier';
    public const ROLE_PAYROLL = 'payroll';
    public const ROLE_FACULTY = 'faculty';

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * Get the faculty account associated with this user.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the transactions recorded by this user.
     */
    public function recordedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recorded_by');
    }

    /**
     * Get the transactions this user authorized override for.
     */
    public function authorizedOverrideTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'override_authorized_by');
    }

    /**
     * Get the corrections requested by this user.
     */
    public function requestedCorrections(): HasMany
    {
        return $this->hasMany(TransactionCorrection::class, 'requested_by');
    }

    /**
     * Get the corrections approved by this user.
     */
    public function approvedCorrections(): HasMany
    {
        return $this->hasMany(TransactionCorrection::class, 'approved_by');
    }

    /**
     * Get the payroll deductions approved by this user.
     */
    public function approvedDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class, 'approved_by');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        return $this->role?->value === $roleValue;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        $roleValues = array_map(fn($r) => $r instanceof UserRole ? $r->value : $r, $roles);
        return in_array($this->role?->value, $roleValues);
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->role?->value === UserRole::ADMINISTRATOR->value;
    }

    /**
     * Check if user is a cashier.
     */
    public function isCashier(): bool
    {
        return $this->role?->value === UserRole::CASHIER->value;
    }

    /**
     * Check if user is a payroll officer.
     */
    public function isPayroll(): bool
    {
        return $this->role?->value === UserRole::PAYROLL->value;
    }

    /**
     * Check if user is a faculty member.
     */
    public function isFaculty(): bool
    {
        return $this->role?->value === UserRole::FACULTY->value;
    }

    /**
     * Check if user can access admin functions.
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdministrator();
    }

    /**
     * Check if user can access cashier functions.
     */
    public function canAccessCashier(): bool
    {
        return $this->isAdministrator() || $this->isCashier();
    }

    /**
     * Check if user can access payroll functions.
     */
    public function canAccessPayroll(): bool
    {
        return $this->isAdministrator() || $this->isPayroll();
    }
}