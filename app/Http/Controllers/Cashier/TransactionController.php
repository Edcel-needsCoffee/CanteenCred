<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display recent transactions for the cashier.
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('limit', 25), 100);
        $dateFrom = $request->get('date_from', now()->subDays(7)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $transactions = Transaction::with(['faculty', 'items.product'])
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('cashier.transactions', compact('transactions', 'dateFrom', 'dateTo'));
    }
}