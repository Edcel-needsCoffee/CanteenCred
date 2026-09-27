<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Faculty;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * List transactions.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['faculty_id', 'date_from', 'date_to', 'status']);
        $perPage = min($request->get('limit', 25), 100);
        $sort = $request->get('sort', 'transaction_date');
        $order = $request->get('order', 'desc');

        $query = Transaction::with(['faculty', 'items.product', 'recordedBy'])
            ->when($filters['faculty_id'] ?? null, fn($q, $id) => $q->where('faculty_id', $id))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            ->orderBy($sort, $order);

        $transactions = $query->paginate($perPage);

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Create a new credit transaction.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faculty_id' => 'required|exists:faculty,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'override_credit_limit' => 'sometimes|boolean',
            'override_reason' => 'required_if:override_credit_limit,true|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'fields' => $validator->errors(),
                ]
            ], 422);
        }

        $faculty = Faculty::findOrFail($validator->validated()['faculty_id']);
        
        if (!$faculty->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'FACULTY_DISABLED',
                    'message' => 'Cannot record transaction for disabled faculty account.',
                ]
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['recorded_by'] = $request->user()->id;
            
            $transaction = $this->transactionService->createTransaction($data);

            return response()->json([
                'data' => $transaction,
                'message' => 'Transaction recorded successfully',
            ], 201);
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'CREDIT_LIMIT_EXCEEDED:')) {
                return response()->json([
                    'error' => [
                        'code' => 'CREDIT_LIMIT_EXCEEDED',
                        'message' => 'This transaction exceeds the faculty member\'s remaining credit limit.',
                        'fields' => [
                            'remaining_credit' => $this->extractRemainingCredit($e->getMessage()),
                            'attempted_total' => $this->extractAttemptedTotal($e->getMessage()),
                        ],
                    ],
                ], 409);
            }

            return response()->json([
                'error' => [
                    'code' => 'TRANSACTION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Get a transaction with details.
     */
    public function show(Transaction $transaction)
    {
        $transaction = $this->transactionService->getTransactionWithDetails($transaction->id);

        if (!$transaction) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Transaction not found.',
                ]
            ], 404);
        }

        return response()->json([
            'data' => $transaction,
        ]);
    }

    /**
     * Void a transaction.
     */
    public function void(Request $request, Transaction $transaction)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'fields' => $validator->errors(),
                ]
            ], 422);
        }

        try {
            $transaction = $this->transactionService->voidTransaction(
                $transaction,
                $request->user(),
                $validator->validated()['reason']
            );

            return response()->json([
                'data' => $transaction,
                'message' => 'Transaction voided successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'VOID_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Get faculty transactions.
     */
    public function facultyTransactions(Request $request, int $facultyId)
    {
        $filters = $request->only(['status', 'date_from', 'date_to']);
        $perPage = min($request->get('limit', 25), 100);

        $transactions = $this->transactionService->getFacultyTransactions($facultyId, $filters, $perPage);

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    private function extractRemainingCredit(string $message): string
    {
        if (preg_match('/Remaining: ([\d.]+)/', $message, $matches)) {
            return $matches[1];
        }
        return '0.00';
    }

    private function extractAttemptedTotal(string $message): string
    {
        if (preg_match('/Attempted: ([\d.]+)/', $message, $matches)) {
            return $matches[1];
        }
        return '0.00';
    }
}