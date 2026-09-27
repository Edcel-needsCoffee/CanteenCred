<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Transaction;
use App\Models\PayrollDeduction;
use App\Services\CreditBalanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected CreditBalanceService $creditBalanceService;

    public function __construct(CreditBalanceService $creditBalanceService)
    {
        $this->creditBalanceService = $creditBalanceService;
    }

    /**
     * Display the faculty dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->faculty_id) {
            abort(404, 'No faculty account linked to this user.');
        }

        $faculty = Faculty::findOrFail($user->faculty_id);
        $balanceDetails = $this->creditBalanceService->getBalanceDetails($faculty);

        // Current payroll period total
        $currentPeriod = \App\Models\PayrollPeriod::open()->first();
        $currentPeriodTotal = 0;
        if ($currentPeriod) {
            $currentPeriodTotal = $this->creditBalanceService->getMonthlyTotal(
                $faculty,
                $currentPeriod->start_date,
                $currentPeriod->end_date
            );
        }

        // Recent transactions
        $recentTransactions = $faculty->transactions()
            ->with('items.product')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        // Deduction status by period
        $deductions = $faculty->payrollDeductions()
            ->with('payrollPeriod')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('faculty.dashboard', compact(
            'faculty',
            'balanceDetails',
            'currentPeriod',
            'currentPeriodTotal',
            'recentTransactions',
            'deductions'
        ));
    }
}