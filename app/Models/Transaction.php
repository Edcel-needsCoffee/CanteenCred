<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'transaction_number',
    'faculty_id',
    'recorded_by',
    'transaction_date',
    'total_amount',
    'status',
    'credit_limit_override',
    'override_reason',
    'override_authorized_by',
    'payroll_deduction_id',
])]
class Transaction extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_CORRECTED = 'corrected';

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'status' => TransactionStatus::class,
            'credit_limit_override' => 'boolean',
        ];
    }

    /**
     * Get the faculty associated with this transaction.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the user who recorded this transaction.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the user who authorized the credit limit override.
     */
    public function overrideAuthorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_authorized_by');
    }

    /**
     * Get the payroll deduction this transaction is linked to.
     */
    public function payrollDeduction(): BelongsTo
    {
        return $this->belongsTo(PayrollDeduction::class, 'payroll_deduction_id');
    }

    /**
     * Get the line items for this transaction.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    /**
     * Get the correction for this transaction.
     */
    public function correction(): HasOne
    {
        return $this->hasOne(TransactionCorrection::class);
    }

    /**
     * Scope for active transactions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for transactions in a date range.
     */
    public function scopeInDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for transactions not linked to a payroll deduction.
     */
    public function scopeUnlinked($query)
    {
        return $query->whereNull('payroll_deduction_id');
    }

    /**
     * Check if transaction can be voided.
     */
    public function canBeVoided(): bool
    {
        return $this->status === self::STATUS_ACTIVE 
            && !$this->payroll_deduction_id;
    }

    /**
     * Check if transaction can be corrected.
     */
    public function canBeCorrected(): bool
    {
        return $this->status !== self::STATUS_VOIDED
            && !$this->payroll_deduction_id;
    }
}