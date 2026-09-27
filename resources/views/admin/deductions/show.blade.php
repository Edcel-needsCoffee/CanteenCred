@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Deduction Details</h1>
        <p class="text-muted mb-0">{{ $payrollDeduction->faculty->name }} ({{ $payrollDeduction->faculty->faculty_number }})</p>
    </div>
    <div class="d-flex gap-2">
        @if ($payrollDeduction->isDraft())
            <form action="{{ route('admin.deductions.approve', ['payrollPeriod' => $payrollDeduction->payrollPeriod, 'payrollDeduction' => $payrollDeduction]) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success" onclick="return confirm('Approve this deduction?');">
                    <i class="bi bi-check-circle me-1"></i>Approve
                </button>
            </form>
            <form action="{{ route('admin.deductions.reject', ['payrollPeriod' => $payrollDeduction->payrollPeriod, 'payrollDeduction' => $payrollDeduction]) }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="notes" value="Rejected by administrator">
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Reject this deduction?');">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </form>
        @elseif ($payrollDeduction->isApproved())
            <form action="{{ route('payroll.deductions.mark-processed', $payrollDeduction) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Mark this deduction as processed?');">
                    <i class="bi bi-check2-circle me-1"></i>Mark Processed
                </button>
            </form>
        @endif
        <a href="{{ route('admin.deductions.index', $payrollDeduction->payrollPeriod) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Deduction Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Period</label>
                    <div>{{ $payrollDeduction->payrollPeriod->label }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Faculty</label>
                    <div>
                        <strong>{{ $payrollDeduction->faculty->name }}</strong>
                        <br>
                        <small class="text-muted">{{ $payrollDeduction->faculty->faculty_number }} | {{ $payrollDeduction->faculty->department }}</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Total Amount</label>
                    <div class="fs-3 fw-bold text-primary">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($payrollDeduction->total_amount, 2) }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Status</label>
                    <span class="{{ $payrollDeduction->status->badgeClass() }} fs-6">{{ $payrollDeduction->status->label() }}</span>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Transactions</label>
                    <div class="fs-4 fw-bold">{{ $payrollDeduction->transactions_count }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Generated</label>
                    <div>{{ $payrollDeduction->generated_at->format('M d, Y H:i') }}</div>
                </div>
                @if ($payrollDeduction->approved_at)
                    <div class="mb-3">
                        <label class="form-label text-muted small">Approved By</label>
                        <div>{{ $payrollDeduction->approvedBy->name }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Approved At</label>
                        <div>{{ $payrollDeduction->approved_at->format('M d, Y H:i') }}</div>
                    </div>
                @endif
                @if ($payrollDeduction->processed_at)
                    <div class="mb-3">
                        <label class="form-label text-muted small">Processed At</label>
                        <div>{{ $payrollDeduction->processed_at->format('M d, Y H:i') }}</div>
                    </div>
                @endif
                @if ($payrollDeduction->notes)
                    <div class="alert alert-secondary">
                        <strong>Notes:</strong> {{ $payrollDeduction->notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Underlying Transactions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Transaction #</th>
                                <th>Items</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payrollDeduction->transactions as $txn)
                                <tr>
                                    <td>{{ $txn->transaction_date->format('M d, Y') }}</td>
                                    <td><code>{{ $txn->transaction_number }}</code></td>
                                    <td>
                                        @foreach ($txn->items as $item)
                                            <div class="small">
                                                {{ $item->product_name_snapshot }} × {{ $item->quantity }}
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="text-end fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($txn->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-active">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold fs-5">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($payrollDeduction->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection