<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Faculty;
use App\Models\User;
use App\Models\AuditLog;
use App\Enums\TransactionStatus;
use App\Enums\ProductStatus;
use App\Enums\AuditAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionService
{
    protected CreditBalanceService $creditBalanceService;

    public function __construct(CreditBalanceService $creditBalanceService)
    {
        $this->creditBalanceService = $creditBalanceService;
    }

    /**
     * Generate a unique transaction number.
     */
    public function generateTransactionNumber(): string
    {
        $prefix = config('app.facu_cred_transaction_prefix', 'TXN');
        $date = Carbon::now()->format('Ymd');
        $sequence = Transaction::whereDate('created_at', Carbon::today())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Create a new credit transaction with line items.
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $faculty = Faculty::findOrFail($data['faculty_id']);
            $cashier = User::findOrFail($data['recorded_by']);
            $items = $data['items'];
            $override = $data['override_credit_limit'] ?? false;
            $overrideReason = $data['override_reason'] ?? null;

            // Validate products and calculate totals
            $lineItems = [];
            $totalAmount = 0;

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->status !== ProductStatus::AVAILABLE) {
                    throw new \Exception("Product '{$product->name}' is not available.");
                }

                $quantity = $item['quantity'];
                $unitPrice = $product->price;
                $subtotal = $quantity * $unitPrice;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];

                $totalAmount += $subtotal;
            }

            // Check credit limit
            $wouldExceed = $this->creditBalanceService->wouldExceedLimit($faculty, $totalAmount);

            if ($wouldExceed) {
                if (!$override) {
                    $remaining = $this->creditBalanceService->getRemainingCredit($faculty);
                    throw new \Exception("CREDIT_LIMIT_EXCEEDED: This transaction exceeds the faculty member's remaining credit limit. Remaining: {$remaining}, Attempted: {$totalAmount}");
                }
                
                // Only administrators can authorize credit limit overrides
                if (!$cashier->isAdministrator()) {
                    $remaining = $this->creditBalanceService->getRemainingCredit($faculty);
                    throw new \Exception("CREDIT_LIMIT_EXCEEDED: This transaction exceeds the faculty member's remaining credit limit. Remaining: {$remaining}, Attempted: {$totalAmount}");
                }
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_number' => $this->generateTransactionNumber(),
                'faculty_id' => $faculty->id,
                'recorded_by' => $cashier->id,
                'transaction_date' => now(),
                'total_amount' => $totalAmount,
                'status' => TransactionStatus::ACTIVE->value,
                'credit_limit_override' => $wouldExceed && $override,
                'override_reason' => $wouldExceed && $override ? $overrideReason : null,
                'override_authorized_by' => $wouldExceed && $override ? $cashier->id : null,
            ]);

            // Create line items
            foreach ($lineItems as $lineItem) {
                TransactionItem::create(array_merge($lineItem, [
                    'transaction_id' => $transaction->id,
                ]));
            }

            // Audit log
            AuditLog::create([
                'user_id' => $cashier->id,
                'action' => $wouldExceed && $override
                    ? AuditAction::TRANSACTION_CREDIT_LIMIT_OVERRIDE->value
                    : AuditAction::TRANSACTION_CREATED->value,
                'subject_type' => Transaction::class,
                'subject_id' => $transaction->id,
                'description' => "Credit transaction {$transaction->transaction_number} recorded for {$faculty->name} ({$faculty->faculty_number})",
                'metadata' => [
                    'faculty_id' => $faculty->id,
                    'faculty_number' => $faculty->faculty_number,
                    'total_amount' => $totalAmount,
                    'credit_limit_override' => $wouldExceed && $override,
                    'override_reason' => $overrideReason,
                    'items' => $lineItems,
                ],
            ]);

            return $transaction->load('items.product', 'faculty', 'recordedBy');
        });
    }

    /**
     * Void a transaction.
     */
    public function voidTransaction(Transaction $transaction, User $user, string $reason): Transaction
    {
        return DB::transaction(function () use ($transaction, $user, $reason) {
            if ($transaction->status !== TransactionStatus::ACTIVE) {
                throw new \Exception('Only active transactions can be voided.');
            }

            if ($transaction->payroll_deduction_id) {
                throw new \Exception('Cannot void a transaction that has been included in a payroll deduction report.');
            }

            $transaction->update([
                'status' => TransactionStatus::VOIDED->value,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => AuditAction::TRANSACTION_VOIDED->value,
                'subject_type' => Transaction::class,
                'subject_id' => $transaction->id,
                'description' => "Transaction {$transaction->transaction_number} voided by {$user->name}",
                'metadata' => [
                    'reason' => $reason,
                    'original_amount' => $transaction->total_amount,
                    'faculty_id' => $transaction->faculty_id,
                ],
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Get transaction with all relationships.
     */
    public function getTransactionWithDetails(int $id): ?Transaction
    {
        return Transaction::with([
            'items.product',
            'faculty',
            'recordedBy',
            'overrideAuthorizedBy',
            'payrollDeduction',
        ])->find($id);
    }

    /**
     * Get faculty transactions with pagination.
     */
    public function getFacultyTransactions(int $facultyId, array $filters = [], int $perPage = 25)
    {
        $query = Transaction::with(['items.product', 'recordedBy'])
            ->where('faculty_id', $facultyId)
            ->orderBy('transaction_date', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }
}