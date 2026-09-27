<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Transaction;
use App\Models\PayrollPeriod;
use App\Models\PayrollDeduction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $openPeriod = PayrollPeriod::open()->first();
        
        // Recent transactions
        $recentTransactions = Transaction::with(['faculty', 'recordedBy'])
            ->active()
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        // Faculty with balances near limit
        $facultyNearLimit = Faculty::active()
            ->with(['activeTransactions'])
            ->get()
            ->filter(fn($f) => $f->remaining_credit > 0 && $f->remaining_credit < 200)
            ->take(5);

        // Stats - compute total balance properly
        $facultyBalances = Faculty::active()->with('activeTransactions')->get();
        $totalBalance = $facultyBalances->sum(fn($f) => $f->balance);
        
        $stats = [
            'total_faculty' => Faculty::active()->count(),
            'total_products' => \App\Models\Product::available()->count(),
            'open_period' => $openPeriod?->label,
            'pending_deductions' => PayrollDeduction::where('status', 'draft')->count(),
            'total_balance' => $totalBalance,
            'today_transactions' => Transaction::active()
                ->whereDate('transaction_date', Carbon::today())
                ->count(),
            'today_amount' => Transaction::active()
                ->whereDate('transaction_date', Carbon::today())
                ->sum('total_amount'),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'recentTransactions',
            'facultyNearLimit',
            'openPeriod'
        ));
    }
}