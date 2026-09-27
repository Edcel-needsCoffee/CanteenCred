<?php

namespace App\Http\Controllers\Admin;

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
     * Display a listing of deductions for a payroll period.
     */
    public function index(Request $request, PayrollPeriod $payrollPeriod)
    {
        $status = $request->get('status');
        $perPage = min($request->get('limit', 25), 100);

        $query = PayrollDeduction::with(['faculty', 'payrollPeriod', 'approvedBy'])
            ->where('payroll_period_id', $payrollPeriod->id)
            ->when($status, function ($q) use ($status) {
                $statuses = explode(',', $status);
                $q->whereIn('status', $statuses);
            })
            ->orderBy('created_at', 'desc');

        $deductions = $query->paginate($perPage)->withQueryString();

        return view('admin.deductions.index', compact('deductions', 'payrollPeriod', 'status'));
    }

    /**
     * Display the specified deduction.
     */
    public function show(PayrollDeduction $payrollDeduction)
    {
        $payrollDeduction->load('faculty', 'payrollPeriod', 'approvedBy', 'transactions.items.product');

        return view('admin.deductions.show', compact('payrollDeduction'));
    }

    /**
     * Generate draft deductions for a closed period.
     */
    public function generate(Request $request, PayrollPeriod $payrollPeriod)
    {
        if (!$payrollPeriod->isClosed()) {
            return redirect()->back()
                ->with('error', 'Can only generate deductions for closed payroll periods.');
        }

        try {
            $this->deductionService->generateDeductions($payrollPeriod, $request->user());

            return redirect()->back()
                ->with('success', 'Draft deductions generated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Approve a deduction.
     */
    public function approve(Request $request, PayrollDeduction $payrollDeduction)
    {
        try {
            $this->deductionService->approveDeduction($payrollDeduction, $request->user());

            return redirect()->back()
                ->with('success', 'Deduction approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
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
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $this->deductionService->rejectDeduction(
                $payrollDeduction,
                $request->user(),
                $validator->validated()['notes'] ?? ''
            );

            return redirect()->back()
                ->with('success', 'Deduction rejected successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Export approved deductions as CSV.
     */
    public function export(Request $request, PayrollPeriod $payrollPeriod)
    {
        try {
            $exportData = $this->deductionService->exportApprovedDeductions($payrollPeriod);

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=payroll-deductions-{$payrollPeriod->label}.csv",
            ];

            return response()->streamDownload(function () use ($exportData) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $exportData['headers']);
                foreach ($exportData['rows'] as $row) {
                    fputcsv($handle, $row);
                }
                fputcsv($handle, ['', '', '', 'Total: ' . number_format($exportData['total'], 2), '', '', '']);
                fputcsv($handle, ['', '', '', 'Count: ' . $exportData['count'], '', '', '']);
                fclose($handle);
            }, "payroll-deductions-{$payrollPeriod->label}.csv", $headers);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
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
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}