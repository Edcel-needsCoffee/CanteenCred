<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Services\PayrollDeductionService;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    protected PayrollDeductionService $deductionService;

    public function __construct(PayrollDeductionService $deductionService)
    {
        $this->deductionService = $deductionService;
    }

    /**
     * Display approved/processed deductions for payroll.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'approved,processed');
        $perPage = min($request->get('limit', 25), 100);
        $periodId = $request->get('payroll_period_id');

        $query = PayrollDeduction::with(['faculty', 'payrollPeriod', 'approvedBy'])
            ->whereIn('status', ['approved', 'processed'])
            ->when($periodId, fn($q) => $q->where('payroll_period_id', $periodId))
            ->when($status, function ($q) use ($status) {
                $statuses = explode(',', $status);
                $q->whereIn('status', $statuses);
            })
            ->orderBy('created_at', 'desc');

        $deductions = $query->paginate($perPage)->withQueryString();
        $periods = PayrollPeriod::orderBy('start_date', 'desc')->get();

        return view('payroll.deductions.index', compact('deductions', 'periods', 'status', 'periodId'));
    }

    /**
     * Display the specified deduction.
     */
    public function show(PayrollDeduction $payrollDeduction)
    {
        if (!$payrollDeduction->isVisibleToPayroll()) {
            abort(403, 'You can only view approved or processed deductions.');
        }

        $payrollDeduction->load('faculty', 'payrollPeriod', 'approvedBy', 'transactions.items.product');

        return view('payroll.deductions.show', compact('payrollDeduction'));
    }

    /**
     * Mark a deduction as processed.
     */
    public function markProcessed(Request $request, PayrollDeduction $payrollDeduction)
    {
        if (!$payrollDeduction->isApproved()) {
            return redirect()->back()
                ->with('error', 'Only approved deductions can be marked as processed.');
        }

        try {
            $this->deductionService->markProcessed($payrollDeduction, $request->user());

            return redirect()->back()
                ->with('success', 'Deduction marked as processed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}