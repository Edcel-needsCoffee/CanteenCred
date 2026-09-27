<?php

namespace App\Models;

use App\Enums\PayrollPeriodStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'label',
    'start_date',
    'end_date',
    'status',
])]
class PayrollPeriod extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => PayrollPeriodStatus::class,
        ];
    }

    /**
     * Get the payroll deductions for this period.
     */
    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    /**
     * Get the transactions in this period.
     */
    public function transactions()
    {
        return Transaction::whereBetween('transaction_date', [$this->start_date, $this->end_date]);
    }

    /**
     * Scope for open periods.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', PayrollPeriodStatus::OPEN->value);
    }

    /**
     * Scope for closed periods.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', PayrollPeriodStatus::CLOSED->value);
    }

    /**
     * Check if period is open for new transactions.
     */
    public function isOpen(): bool
    {
        return $this->status === PayrollPeriodStatus::OPEN->value;
    }

    /**
     * Check if period is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === PayrollPeriodStatus::CLOSED->value;
    }

    /**
     * Check if period is finalized.
     */
    public function isFinalized(): bool
    {
        return $this->status === PayrollPeriodStatus::FINALIZED->value;
    }
}