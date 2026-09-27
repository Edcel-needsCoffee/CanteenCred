<?php

namespace App\Models;

use App\Enums\CorrectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'requested_by',
    'original_snapshot',
    'corrected_snapshot',
    'reason',
    'approved_by',
    'approved_at',
    'status',
])]
class TransactionCorrection extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'original_snapshot' => 'array',
            'corrected_snapshot' => 'array',
            'approved_at' => 'datetime',
            'status' => CorrectionStatus::class,
        ];
    }

    /**
     * Get the transaction this correction is for.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the user who requested this correction.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved this correction.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if correction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === CorrectionStatus::PENDING->value;
    }

    /**
     * Check if correction is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === CorrectionStatus::APPROVED->value;
    }

    /**
     * Check if correction is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === CorrectionStatus::REJECTED->value;
    }

    /**
     * Get the original transaction total from snapshot.
     */
    public function getOriginalTotalAttribute(): float
    {
        return $this->original_snapshot['transaction']['total_amount'] ?? 0;
    }

    /**
     * Get the corrected transaction total from snapshot.
     */
    public function getCorrectedTotalAttribute(): float
    {
        return $this->corrected_snapshot['transaction']['total_amount'] ?? 0;
    }

    /**
     * Get the difference between corrected and original.
     */
    public function getDifferenceAttribute(): float
    {
        return $this->corrected_total - $this->original_total;
    }
}