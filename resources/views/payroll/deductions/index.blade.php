@extends('layouts.payroll')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Approved Deductions</h1>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="payroll_period_id" class="form-select">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" {{ request('payroll_period_id') == $period->id ? 'selected' : '' }}>
                            {{ $period->label }} ({{ $period->status->label() }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="approved,processed" {{ request('status') == 'approved,processed' ? 'selected' : '' }}>Approved & Processed</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('payroll.deductions.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if ($deductions->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No approved deductions found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
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
                                <td>{{ $deduction->payrollPeriod->label }}</td>
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
                                        <a href="{{ route('payroll.deductions.show', $deduction) }}" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($deduction->isApproved())
                                            <form action="{{ route('payroll.deductions.mark-processed', $deduction) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Mark Processed" onclick="return confirm('Mark this deduction as processed?');">
                                                    <i class="bi bi-check2-circle"></i>
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