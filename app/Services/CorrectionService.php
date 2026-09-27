<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionCorrection;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\AuditLog;
use App\Enums\CorrectionStatus;
use App\Enums\AuditAction;
use Illuminate\Support\Facades\DB;

class CorrectionService
{
    /**
     * Submit a correction request.
     */
    public function requestCorrection(
        Transaction $transaction,
        User $requestedBy,
        array $correctedData,
        string $reason
    ): TransactionCorrection {
        return DB::transaction(function () use ($transaction, $requestedBy, $correctedData, $reason) {
            if ($transaction->payroll_deduction_id) {
                throw new \Exception('Cannot correct a transaction that has been included in an approved payroll deduction.');
            }

            // Capture original snapshot
            $originalSnapshot = [
                'transaction' => [
                    'transaction_number' => $transaction->transaction_number,
                    'faculty_id' => $transaction->faculty_id,
                    'total_amount' => $transaction->total_amount,
                    'status' => $transaction->status,
                    'credit_limit_override' => $transaction->credit_limit_override,
                    'override_reason' => $transaction->override_reason,
                    'override_authorized_by' => $transaction->override_authorized_by,
                ],
                'items' => $transaction->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name_snapshot' => $item->product_name_snapshot,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                    ];
                })->toArray(),
            ];

            // Prepare corrected snapshot
            $correctedSnapshot = [
                'transaction' => array_merge($originalSnapshot['transaction'], $correctedData['transaction'] ?? []),
                'items' => $correctedData['items'] ?? $originalSnapshot['items'],
            ];

            // Calculate corrected total
            $correctedTotal = collect($correctedSnapshot['items'])->sum('subtotal');
            $correctedSnapshot['transaction']['total_amount'] = $correctedTotal;

            $correction = TransactionCorrection::create([
                'transaction_id' => $transaction->id,
                'requested_by' => $requestedBy->id,
                'original_snapshot' => $originalSnapshot,
                'corrected_snapshot' => $correctedSnapshot,
                'reason' => $reason,
                'status' => CorrectionStatus::PENDING->value,
            ]);

            AuditLog::create([
                'user_id' => $requestedBy->id,
                'action' => AuditAction::CORRECTION_REQUESTED->value,
                'subject_type' => TransactionCorrection::class,
                'subject_id' => $correction->id,
                'description' => "Correction requested for transaction {$transaction->transaction_number} by {$requestedBy->name}",
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'original_total' => $originalSnapshot['transaction']['total_amount'],
                    'corrected_total' => $correctedTotal,
                    'reason' => $reason,
                ],
            ]);

            return $correction;
        });
    }

    /**
     * Approve a correction request.
     */
    public function approveCorrection(TransactionCorrection $correction, User $approver): TransactionCorrection
    {
        return DB::transaction(function () use ($correction, $approver) {
            if ($correction->status !== CorrectionStatus::PENDING->value) {
                throw new \Exception('Only pending corrections can be approved.');
            }

            $transaction = $correction->transaction;

            if ($transaction->payroll_deduction_id) {
                throw new \Exception('Cannot correct a transaction that has been included in an approved payroll deduction.');
            }

            $correctedData = $correction->corrected_snapshot;

            // Update transaction
            $transaction->update([
                'total_amount' => $correctedData['transaction']['total_amount'],
                'status' => Transaction::STATUS_CORRECTED,
                'credit_limit_override' => $correctedData['transaction']['credit_limit_override'] ?? false,
                'override_reason' => $correctedData['transaction']['override_reason'] ?? null,
                'override_authorized_by' => $correctedData['transaction']['override_authorized_by'] ?? null,
            ]);

            // Update line items
            $transaction->items()->delete();

            foreach ($correctedData['items'] as $item) {
                TransactionItem::create(array_merge($item, [
                    'transaction_id' => $transaction->id,
                ]));
            }

            $correction->update([
                'status' => CorrectionStatus::APPROVED->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $approver->id,
                'action' => AuditAction::CORRECTION_APPROVED->value,
                'subject_type' => TransactionCorrection::class,
                'subject_id' => $correction->id,
                'description' => "Correction approved for transaction {$transaction->transaction_number} by {$approver->name}",
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'original_total' => $correction->original_snapshot['transaction']['total_amount'],
                    'corrected_total' => $correctedData['transaction']['total_amount'],
                ],
            ]);

            return $correction->fresh();
        });
    }

    /**
     * Reject a correction request.
     */
    public function rejectCorrection(TransactionCorrection $correction, User $rejecter): TransactionCorrection
    {
        $correction->update([
            'status' => CorrectionStatus::REJECTED->value,
        ]);

        AuditLog::create([
            'user_id' => $rejecter->id,
            'action' => AuditAction::CORRECTION_REJECTED->value,
            'subject_type' => TransactionCorrection::class,
            'subject_id' => $correction->id,
            'description' => "Correction rejected for transaction {$correction->transaction->transaction_number} by {$rejecter->name}",
            'metadata' => [
                'transaction_id' => $correction->transaction_id,
            ],
        ]);

        return $correction->fresh();
    }

    /**
     * Get correction requests with filters.
     */
    public function getCorrections(array $filters = [], int $perPage = 25)
    {
        $query = TransactionCorrection::with(['transaction.faculty', 'requestedBy', 'approvedBy'])
            ->latest();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['transaction_id'])) {
            $query->where('transaction_id', $filters['transaction_id']);
        }

        return $query->paginate($perPage);
    }
}