@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">{{ $payrollPeriod->label }}</h1>
        <p class="text-muted mb-0">{{ $payrollPeriod->start_date->format('M d, Y') }} - {{ $payrollPeriod->end_date->format('M d, Y') }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($payrollPeriod->isOpen())
            <form action="{{ route('admin.payroll-periods.close', $payrollPeriod) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Close this period? No new transactions can be added.');">
                    <i class="bi bi-lock me-1"></i>Close Period
                </button>
            </form>
        @endif
        <a href="{{ route('admin.payroll-periods.index') }}" class="btn btn-outline-secondary">
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
                <h5 class="mb-0">Period Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Status</label>
                    <span class="{{ $payrollPeriod->status->badgeClass() }} fs-6">{{ $payrollPeriod->status->label() }}</span>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Start Date</label>
                    {{ $payrollPeriod->start_date->format('M d, Y') }}
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">End Date</label>
                    {{ $payrollPeriod->end_date->format('M d, Y') }}
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Deductions Generated</label>
                    <div class="fs-4 fw-bold">{{ $payrollPeriod->deductions_count }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Created</label>
                    {{ $payrollPeriod->created_at->format('M d, Y H:i') }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payroll Deductions</h5>
                @if ($payrollPeriod->isClosed() && $payrollPeriod->deductions_count == 0)
                    <form action="{{ route('admin.deductions.generate', $payrollPeriod) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Generate draft deductions for this closed period?');">
                            <i class="bi bi-file-earmark-plus me-1"></i>Generate Deductions
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($payrollPeriod->deductions_count == 0)
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No deductions generated yet.</p>
                        @if ($payrollPeriod->isClosed())
                            <p class="text-muted small">Click "Generate Deductions" to create draft reports.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Faculty</th>
                                    <th>Department</th>
                                    <th>Transactions</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                    <th>Approved At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payrollPeriod->deductions as $deduction)
                                    <tr>
                                        <td>
                                            <strong>{{ $deduction->faculty->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $deduction->faculty->faculty_number }}</small>
                                        </td>
                                        <td>{{ $deduction->faculty->department }}</td>
                                        <td>{{ $deduction->transactions_count }}</td>
                                        <td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($deduction->total_amount, 2) }}</td>
                                        <td><span class="{{ $deduction->status->badgeClass() }}">{{ $deduction->status->label() }}</span></td>
                                        <td>{{ $deduction->approvedBy->name ?? '—' }}</td>
                                        <td>{{ $deduction->approved_at ? $deduction->approved_at->format('M d, Y H:i') : '—' }}</td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.deductions.show', ['payrollPeriod' => $payrollPeriod, 'payrollDeduction' => $deduction]) }}" class="btn btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if ($deduction->isDraft())
                                                    <form action="{{ route('admin.deductions.approve', ['payrollPeriod' => $payrollPeriod, 'payrollDeduction' => $deduction]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-success" title="Approve" onclick="return confirm('Approve this deduction?');">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.deductions.reject', ['payrollPeriod' => $payrollPeriod, 'payrollDeduction' => $deduction]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="notes" value="Rejected by administrator">
                                                        <button type="submit" class="btn btn-outline-danger" title="Reject" onclick="return confirm('Reject this deduction?');">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection