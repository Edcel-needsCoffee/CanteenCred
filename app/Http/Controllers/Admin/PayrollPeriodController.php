<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollPeriodController extends Controller
{
    /**
     * Display a listing of payroll periods.
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('limit', 25), 100);

        $periods = PayrollPeriod::withCount('deductions')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.payroll-periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new payroll period.
     */
    public function create()
    {
        return view('admin.payroll-periods.create');
    }

    /**
     * Store a newly created payroll period.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $period = PayrollPeriod::create($validator->validated() + ['status' => 'open']);

        return redirect()->route('admin.payroll-periods.index')
            ->with('success', 'Payroll period created successfully.');
    }

    /**
     * Display the specified payroll period.
     */
    public function show(PayrollPeriod $payrollPeriod)
    {
        $payrollPeriod->loadCount('deductions');

        return view('admin.payroll-periods.show', compact('payrollPeriod'));
    }

    /**
     * Show the form for editing the specified payroll period.
     */
    public function edit(PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isOpen()) {
            return redirect()->back()
                ->with('error', 'Cannot edit a closed or finalized period.');
        }

        return view('admin.payroll-periods.edit', compact('payrollPeriod'));
    }

    /**
     * Update the specified payroll period.
     */
    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isOpen()) {
            return redirect()->back()
                ->with('error', 'Cannot update a closed or finalized period.');
        }

        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|string|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $payrollPeriod->update($validator->validated());

        return redirect()->route('admin.payroll-periods.index')
            ->with('success', 'Payroll period updated successfully.');
    }

    /**
     * Close a payroll period.
     */
    public function close(Request $request, PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isOpen()) {
            return redirect()->back()
                ->with('error', 'Only open periods can be closed.');
        }

        $payrollPeriod->update(['status' => 'closed']);

        return redirect()->route('admin.payroll-periods.index')
            ->with('success', 'Payroll period closed successfully.');
    }
}