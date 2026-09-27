<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'faculty_number',
    'name',
    'department',
    'credit_limit',
    'is_active',
])]
class Faculty extends Model
{
    protected $table = 'faculty';
    use HasFactory;

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user account linked to this faculty.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'faculty_id');
    }

    /**
     * Get all transactions for this faculty.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get active transactions for this faculty.
     */
    public function activeTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('status', 'active');
    }

    /**
     * Get payroll deductions for this faculty.
     */
    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    /**
     * Get the running credit balance.
     */
    public function getBalanceAttribute(): float
    {
        return $this->activeTransactions()->sum('total_amount');
    }

    /**
     * Get the remaining credit limit.
     */
    public function getRemainingCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->balance);
    }

    /**
     * Check if a transaction amount would exceed the credit limit.
     */
    public function wouldExceedLimit(float $amount): bool
    {
        return $amount > $this->remaining_credit;
    }

    /**
     * Scope for active faculty.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for searching faculty.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('faculty_number', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%");
        });
    }
}