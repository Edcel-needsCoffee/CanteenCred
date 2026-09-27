<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollPeriodController extends Controller
{
    /**
     * List payroll periods.
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('limit', 25), 100);

        $periods = PayrollPeriod::withCount('deductions')
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $periods->items(),
            'meta' => [
                'current_page' => $periods->currentPage(),
                'per_page' => $periods->perPage(),
                'total' => $periods->total(),
            ],
        ]);
    }

    /**
     * Create a new payroll period.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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

        $period = PayrollPeriod::create($validator->validated() + ['status' => 'open']);

        return response()->json([
            'data' => $period,
            'message' => 'Payroll period created successfully',
        ], 201);
    }

    /**
     * Get a payroll period.
     */
    public function show(PayrollPeriod $payrollPeriod)
    {
        return response()->json([
            'data' => $payrollPeriod->loadCount('deductions'),
        ]);
    }

    /**
     * Update a payroll period (only if open).
     */
    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isOpen()) {
            return response()->json([
                'error' => [
                    'code' => 'PERIOD_NOT_OPEN',
                    'message' => 'Cannot update a closed or finalized period.',
                ]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|string|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
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

        $payrollPeriod->update($validator->validated());

        return response()->json([
            'data' => $payrollPeriod,
            'message' => 'Payroll period updated successfully',
        ]);
    }

    /**
     * Close a payroll period.
     */
    public function close(Request $request, PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isOpen()) {
            return response()->json([
                'error' => [
                    'code' => 'PERIOD_NOT_OPEN',
                    'message' => 'Only open periods can be closed.',
                ]
            ], 422);
        }

        $payrollPeriod->update(['status' => 'closed']);

        return response()->json([
            'data' => $payrollPeriod,
            'message' => 'Payroll period closed successfully',
        ]);
    }
}