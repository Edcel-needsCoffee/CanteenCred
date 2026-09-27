@extends('layouts.faculty')

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title text-white-50">Credit Limit</h6>
                <h2 class="mb-0">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($balanceDetails['credit_limit'], 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title text-white-50">Current Balance</h6>
                <h2 class="mb-0">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($balanceDetails['current_balance'], 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card {{ $balanceDetails['remaining_credit'] < 200 ? 'bg-danger' : 'bg-info' }} text-white">
            <div class="card-body">
                <h6 class="card-title text-white-50">Remaining Credit</h6>
                <h2 class="mb-0">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($balanceDetails['remaining_credit'], 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-3">
    <div class="col-md-6">
        @if ($currentPeriod)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Period: {{ $currentPeriod->label }}</h5>
                </div>
                <div class="card-body text-center">
                    <h3 class="text-primary mb-0">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($currentPeriodTotal, 2) }}</h3>
                    <p class="text-muted mb-0">Total this period</p>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center text-muted">
                    <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No open payroll period</p>
                </div>
            </div>
        @endif
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Account Info</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Faculty #:</strong> {{ $faculty->faculty_number }}</p>
                <p class="mb-1"><strong>Department:</strong> {{ $faculty->department }}</p>
                <p class="mb-0"><strong>Credit Usage:</strong> {{ $balanceDetails['percentage_used'] }}%</p>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="{{ route('faculty.transactions') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                @if ($recentTransactions->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-receipt" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No transactions yet</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($recentTransactions as $txn)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $txn->transaction_number }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $txn->transaction_date->format('M d, Y H:i') }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($txn->total_amount, 2) }}</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    @foreach ($txn->items as $item)
                                        {{ $item->product_name_snapshot }} × {{ $item->quantity }}{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Deduction Status</h5>
                <a href="{{ route('faculty.deductions') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                @if ($deductions->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-earmark-text" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No deduction records</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($deductions as $deduction)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $deduction->payrollPeriod->label }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $deduction->payrollPeriod->start_date->format('M d, Y') }} - {{ $deduction->payrollPeriod->end_date->format('M d, Y') }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($deduction->total_amount, 2) }}</div>
                                        <span class="{{ $deduction->status->badgeClass() }}">{{ $deduction->status->label() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection