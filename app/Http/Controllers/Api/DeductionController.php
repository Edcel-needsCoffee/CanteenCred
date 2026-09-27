<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Services\PayrollDeductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeductionController extends Controller
{
    protected PayrollDeductionService $deductionService;

    public function __construct(PayrollDeductionService $deductionService)
    {
        $this->deductionService = $deductionService;
    }

    /**
     * List deductions for a payroll period.
     */
    public function index(Request $request, PayrollPeriod $payrollPeriod)
    {
        $user = $request->user();
        $status = $request->get('status');
        $perPage = min($request->get('limit', 25), 100);

        // Payroll users can only see approved/processed deductions
        if ($user->isPayroll() && !$user->isAdministrator()) {
            $status = $status ?? 'approved,processed';
        }

        $query = PayrollDeduction::with(['faculty', 'payrollPeriod', 'approvedBy'])
            ->where('payroll_period_id', $payrollPeriod->id)
            ->when($status, function ($q) use ($status) {
                $statuses = explode(',', $status);
                $q->whereIn('status', $statuses);
            })
            ->orderBy('created_at', 'desc');

        $deductions = $query->paginate($perPage);

        return response()->json([
            'data' => $deductions->items(),
            'meta' => [
                'current_page' => $deductions->currentPage(),
                'per_page' => $deductions->perPage(),
                'total' => $deductions->total(),
            ],
        ]);
    }

    /**
     * Get a deduction with details.
     */
    public function show(PayrollDeduction $payrollDeduction)
    {
        $user = request()->user();

        // Payroll users can only see approved/processed
        if ($user->isPayroll() && !$user->isAdministrator() && !$payrollDeduction->isVisibleToPayroll()) {
            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You can only view approved or processed deductions.',
                ]
            ], 403);
        }

        return response()->json([
            'data' => $payrollDeduction->load('faculty', 'payrollPeriod', 'approvedBy', 'transactions.items.product'),
        ]);
    }

    /**
     * Generate draft deductions for a closed period.
     */
    public function generate(Request $request, PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isClosed()) {
            return response()->json([
                'error' => [
                    'code' => 'PERIOD_NOT_CLOSED',
                    'message' => 'Can only generate deductions for closed payroll periods.',
                ]
            ], 422);
        }

        try {
            $deductions = $this->deductionService->generateDeductions($payrollPeriod, $request->user());

            return response()->json([
                'data' => $deductions->load('faculty', 'payrollPeriod'),
                'message' => 'Draft deductions generated successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'GENERATION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Approve a deduction.
     */
    public function approve(Request $request, PayrollDeduction $payrollDeduction)
    {
        try {
            $deduction = $this->deductionService->approveDeduction($payrollDeduction, $request->user());

            return response()->json([
                'data' => $deduction->load('faculty', 'payrollPeriod', 'approvedBy'),
                'message' => 'Deduction approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'APPROVAL_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Reject a deduction.
     */
    public function reject(Request $request, PayrollDeduction $payrollDeduction)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'sometimes|string|max:1000',
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
            $deduction = $this->deductionService->rejectDeduction(
                $payrollDeduction,
                $request->user(),
                $validator->validated()['notes'] ?? ''
            );

            return response()->json([
                'data' => $deduction->load('faculty', 'payrollPeriod'),
                'message' => 'Deduction rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REJECTION_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Mark a deduction as processed.
     */
    public function markProcessed(Request $request, PayrollDeduction $payrollDeduction)
    {
        if (!$payrollDeduction->isApproved()) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_APPROVED',
                    'message' => 'Only approved deductions can be marked as processed.',
                ]
            ], 422);
        }

        try {
            $deduction = $this->deductionService->markProcessed($payrollDeduction, $request->user());

            return response()->json([
                'data' => $deduction->load('faculty', 'payrollPeriod'),
                'message' => 'Deduction marked as processed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'PROCESS_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Export approved deductions as CSV.
     */
    public function export(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $exportData = $this->deductionService->exportApprovedDeductions($payrollPeriod);

            return response()->streamDownload(function () use ($exportData) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $exportData['headers']);
                foreach ($exportData['rows'] as $row) {
                    fputcsv($handle, $row);
                }
                // Add totals row
                fputcsv($handle, ['', '', '', 'Total: ' . number_format($exportData['total'], 2), '', '', '']);
                fputcsv($handle, ['', '', '', 'Count: ' . $exportData['count'], '', '', '']);
                fclose($handle);
            }, "payroll-deductions-{$payrollPeriod->label}.csv", [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=payroll-deductions-{$payrollPeriod->label}.csv",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'EXPORT_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }

    /**
     * Export approved deductions as PDF.
     */
    public function exportPdf(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $pdfContent = $this->deductionService->exportApprovedDeductionsPdf($payrollPeriod);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=payroll-deductions-{$payrollPeriod->label}.pdf",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'EXPORT_FAILED',
                    'message' => $e->getMessage(),
                ]
            ], 500);
        }
    }
}