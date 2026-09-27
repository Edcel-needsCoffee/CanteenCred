<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollDeduction;
use App\Models\Transaction;
use App\Models\Faculty;
use App\Models\User;
use App\Models\AuditLog;
use App\Enums\PayrollPeriodStatus;
use App\Enums\PayrollDeductionStatus;
use App\Enums\AuditAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Dompdf\Dompdf;
use Dompdf\Options;

class PayrollDeductionService
{
    /**
     * Generate draft deductions for a closed payroll period.
     */
    public function generateDeductions(PayrollPeriod $period, User $generatedBy): Collection
    {
        return DB::transaction(function () use ($period, $generatedBy) {
            if ($period->status !== PayrollPeriodStatus::CLOSED) {
                throw new \Exception('Can only generate deductions for closed payroll periods.');
            }

            // Check if deductions already exist
            $existingCount = PayrollDeduction::where('payroll_period_id', $period->id)->count();
            if ($existingCount > 0) {
                throw new \Exception('Deductions have already been generated for this period.');
            }

            // Get all active transactions within the period that are not already linked to a deduction
            $transactions = Transaction::where('status', Transaction::STATUS_ACTIVE)
                ->whereNull('payroll_deduction_id')
                ->whereBetween('transaction_date', [$period->start_date, $period->end_date])
                ->with('faculty')
                ->get();

            // Group by faculty
            $grouped = $transactions->groupBy('faculty_id');

            $deductions = collect();

            foreach ($grouped as $facultyId => $facultyTransactions) {
                $faculty = Faculty::find($facultyId);

                if (!$faculty || !$faculty->is_active) {
                    continue;
                }

                $totalAmount = $facultyTransactions->sum('total_amount');

                $deduction = PayrollDeduction::create([
                    'payroll_period_id' => $period->id,
                    'faculty_id' => $faculty->id,
                    'total_amount' => $totalAmount,
                    'status' => PayrollDeductionStatus::DRAFT->value,
                    'generated_at' => now(),
                ]);

                // Link transactions to this deduction
                $facultyTransactions->each(function ($transaction) use ($deduction) {
                    $transaction->update(['payroll_deduction_id' => $deduction->id]);
                });

                AuditLog::create([
                    'user_id' => $generatedBy->id,
                    'action' => AuditAction::DEDUCTION_GENERATED->value,
                    'subject_type' => PayrollDeduction::class,
                    'subject_id' => $deduction->id,
                    'description' => "Draft deduction generated for {$faculty->name} ({$faculty->faculty_number}) in period {$period->label}",
                    'metadata' => [
                        'faculty_id' => $faculty->id,
                        'faculty_number' => $faculty->faculty_number,
                        'total_amount' => $totalAmount,
                        'transaction_count' => $facultyTransactions->count(),
                        'payroll_period_id' => $period->id,
                    ],
                ]);

                $deductions->push($deduction);
            }

            return $deductions;
        });
    }

    /**
     * Approve a deduction.
     */
    public function approveDeduction(PayrollDeduction $deduction, User $approver): PayrollDeduction
    {
        return DB::transaction(function () use ($deduction, $approver) {
            if ($deduction->status !== PayrollDeductionStatus::DRAFT) {
                throw new \Exception('Only draft deductions can be approved.');
            }

            // Verify the total matches the linked transactions
            $transactionTotal = $deduction->transactions()->sum('total_amount');

            if (abs($transactionTotal - $deduction->total_amount) > 0.01) {
                throw new \Exception('Deduction total does not match underlying transactions.');
            }

            $deduction->update([
                'status' => PayrollDeductionStatus::APPROVED->value,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $approver->id,
                'action' => AuditAction::DEDUCTION_APPROVED->value,
                'subject_type' => PayrollDeduction::class,
                'subject_id' => $deduction->id,
                'description' => "Deduction approved for {$deduction->faculty->name} ({$deduction->faculty->faculty_number})",
                'metadata' => [
                    'faculty_id' => $deduction->faculty_id,
                    'total_amount' => $deduction->total_amount,
                    'payroll_period_id' => $deduction->payroll_period_id,
                ],
            ]);

            return $deduction->fresh();
        });
    }

    /**
     * Reject a deduction.
     */
    public function rejectDeduction(PayrollDeduction $deduction, User $rejecter, string $notes = ''): PayrollDeduction
    {
        return DB::transaction(function () use ($deduction, $rejecter, $notes) {
            if ($deduction->status !== PayrollDeductionStatus::DRAFT) {
                throw new \Exception('Only draft deductions can be rejected.');
            }

            // Unlink transactions
            $deduction->transactions()->update(['payroll_deduction_id' => null]);

            $deduction->update([
                'status' => PayrollDeductionStatus::REJECTED->value,
                'notes' => $notes,
            ]);

            AuditLog::create([
                'user_id' => $rejecter->id,
                'action' => AuditAction::DEDUCTION_REJECTED->value,
                'subject_type' => PayrollDeduction::class,
                'subject_id' => $deduction->id,
                'description' => "Deduction rejected for {$deduction->faculty->name} ({$deduction->faculty->faculty_number})",
                'metadata' => [
                    'faculty_id' => $deduction->faculty_id,
                    'total_amount' => $deduction->total_amount,
                    'notes' => $notes,
                    'payroll_period_id' => $deduction->payroll_period_id,
                ],
            ]);

            return $deduction->fresh();
        });
    }

    /**
     * Mark deduction as processed.
     */
    public function markProcessed(PayrollDeduction $deduction, User $processor): PayrollDeduction
    {
        return DB::transaction(function () use ($deduction, $processor) {
            if ($deduction->status !== PayrollDeductionStatus::APPROVED) {
                throw new \Exception('Only approved deductions can be marked as processed.');
            }

            $deduction->update([
                'status' => PayrollDeductionStatus::PROCESSED->value,
                'processed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $processor->id,
                'action' => AuditAction::DEDUCTION_PROCESSED->value,
                'subject_type' => PayrollDeduction::class,
                'subject_id' => $deduction->id,
                'description' => "Deduction marked as processed for {$deduction->faculty->name} ({$deduction->faculty->faculty_number})",
                'metadata' => [
                    'faculty_id' => $deduction->faculty_id,
                    'total_amount' => $deduction->total_amount,
                    'payroll_period_id' => $deduction->payroll_period_id,
                ],
            ]);

            return $deduction->fresh();
        });
    }

    /**
     * Export approved deductions as CSV data.
     */
    public function exportApprovedDeductions(PayrollPeriod $period): array
    {
        $deductions = PayrollDeduction::with('faculty')
            ->where('payroll_period_id', $period->id)
            ->where('status', PayrollDeductionStatus::APPROVED->value)
            ->orWhere('status', PayrollDeductionStatus::PROCESSED->value)
            ->orderBy('faculty_number')
            ->get();

        $headers = ['Faculty Number', 'Faculty Name', 'Department', 'Total Amount', 'Status', 'Approved At', 'Processed At'];
        $rows = [];

        foreach ($deductions as $deduction) {
            $rows[] = [
                $deduction->faculty->faculty_number,
                $deduction->faculty->name,
                $deduction->faculty->department,
                number_format($deduction->total_amount, 2, '.', ''),
                $deduction->status->label(),
                $deduction->approved_at?->format('Y-m-d H:i:s') ?? '',
                $deduction->processed_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total' => $deductions->sum('total_amount'),
            'count' => $deductions->count(),
        ];
    }

    /**
     * Export approved deductions as PDF.
     */
    public function exportApprovedDeductionsPdf(PayrollPeriod $period): string
    {
        $exportData = $this->exportApprovedDeductions($period);

        $html = view('exports.payroll-deductions-pdf', [
            'period' => $period,
            'deductions' => PayrollDeduction::with('faculty')
                ->where('payroll_period_id', $period->id)
                ->whereIn('status', [PayrollDeductionStatus::APPROVED->value, PayrollDeductionStatus::PROCESSED->value])
                ->orderBy('faculty_number')
                ->get(),
            'total' => $exportData['total'],
            'count' => $exportData['count'],
            'currencySymbol' => config('app.facu_cred_currency_symbol', 'P'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}