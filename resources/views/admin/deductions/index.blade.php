@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Payroll Deductions - {{ $payrollPeriod->label }}</h1>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.deductions.export', $payrollPeriod) }}">
            @csrf
            <button type="submit" class="btn btn-outline-success" title="Export CSV">
                <i class="bi bi-download me-1"></i>Export CSV
            </button>
        </form>
        <a href="{{ route('admin.payroll-periods.show', $payrollPeriod) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Period
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.deductions.index', $payrollPeriod) }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if ($deductions->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No deductions found.</p>
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
                        @foreach ($deductions as $deduction)
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

            <div class="card-footer">
                {{ $deductions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection