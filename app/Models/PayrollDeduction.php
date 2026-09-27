<?php

namespace App\Models;

use App\Enums\PayrollDeductionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'payroll_period_id',
    'faculty_id',
    'total_amount',
    'status',
    'generated_at',
    'approved_by',
    'approved_at',
    'processed_at',
    'notes',
])]
class PayrollDeduction extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'status' => PayrollDeductionStatus::class,
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the payroll period this deduction belongs to.
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Get the faculty this deduction is for.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the user who approved this deduction.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the transactions linked to this deduction.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payroll_deduction_id');
    }

    /**
     * Check if deduction is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === PayrollDeductionStatus::DRAFT;
    }

    /**
     * Check if deduction is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === PayrollDeductionStatus::APPROVED;
    }

    /**
     * Check if deduction is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === PayrollDeductionStatus::REJECTED;
    }

    /**
     * Check if deduction is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === PayrollDeductionStatus::PROCESSED;
    }

    /**
     * Check if deduction is visible to payroll.
     */
    public function isVisibleToPayroll(): bool
    {
        return in_array($this->status, [
            PayrollDeductionStatus::APPROVED,
            PayrollDeductionStatus::PROCESSED,
        ]);
    }

    /**
     * Check if deduction can be edited.
     */
    public function isEditable(): bool
    {
        return $this->isDraft();
    }
}