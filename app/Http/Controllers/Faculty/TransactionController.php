<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display the faculty's transaction history.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            abort(404, 'No faculty account linked to this user.');
        }

        $perPage = min($request->get('limit', 25), 100);
        $filters = $request->only(['status', 'date_from', 'date_to']);

        $transactions = Transaction::with('items.product')
            ->where('faculty_id', $user->faculty_id)
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('transaction_date', '<=', $d))
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('faculty.transactions', compact('transactions', 'filters'));
    }
}