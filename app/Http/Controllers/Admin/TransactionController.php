<?php

namespace App\Http\Controllers\Admin;

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
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['faculty_id', 'date_from', 'date_to', 'status']);
        $perPage = min($request->get('limit', 25), 100);

        $query = Transaction::with(['faculty', 'items.product', 'recordedBy'])
            ->when($filters['faculty_id'] ?? null, fn($q, $id) => $q->where('faculty_id', $id))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            ->orderBy('transaction_date', 'desc');

        $transactions = $query->paginate($perPage)->withQueryString();
        $facultyList = Faculty::active()->orderBy('name')->get(['id', 'faculty_number', 'name']);

        return view('admin.transactions.index', compact('transactions', 'filters', 'facultyList'));
    }

    /**
     * Show the form for creating a new transaction (manual entry).
     */
    public function create()
    {
        $facultyList = Faculty::active()->orderBy('name')->get(['id', 'faculty_number', 'name']);
        $products = \App\Models\Product::available()->orderBy('name')->get(['id', 'name', 'price']);

        return view('admin.transactions.create', compact('facultyList', 'products'));
    }

    /**
     * Store a newly created transaction.
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
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $validator->validated();
            $data['recorded_by'] = $request->user()->id;

            $transaction = $this->transactionService->createTransaction($data);

            return redirect()->route('admin.transactions.show', $transaction)
                ->with('success', 'Transaction recorded successfully.');
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'CREDIT_LIMIT_EXCEEDED:')) {
                return redirect()->back()
                    ->with('error', 'This transaction exceeds the faculty member\'s remaining credit limit.')
                    ->withInput();
            }

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction)
    {
        $transaction = $this->transactionService->getTransactionWithDetails($transaction->id);

        return view('admin.transactions.show', compact('transaction'));
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
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $this->transactionService->voidTransaction(
                $transaction,
                $request->user(),
                $validator->validated()['reason']
            );

            return redirect()->back()
                ->with('success', 'Transaction voided successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}