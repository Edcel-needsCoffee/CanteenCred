@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
</div>
@endsection

@section('content')
<div class="row g-4">
    {{-- Stats Cards --}}
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Total Faculty</h6>
                        <h2 class="mb-0">{{ $stats['total_faculty'] }}</h2>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Active Products</h6>
                        <h2 class="mb-0">{{ $stats['total_products'] }}</h2>
                    </div>
                    <i class="bi bi-box fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Open Period</h6>
                        <h2 class="mb-0">{{ $stats['open_period'] ?? 'None' }}</h2>
                    </div>
                    <i class="bi bi-calendar fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Pending Deductions</h6>
                        <h2 class="mb-0">{{ $stats['pending_deductions'] }}</h2>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Today's Activity --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Today's Activity</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h3 class="text-primary">{{ $stats['today_transactions'] }}</h3>
                        <small class="text-muted">Transactions</small>
                    </div>
                    <div class="col-6">
                        <h3 class="text-success">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($stats['today_amount'], 2) }}</h3>
                        <small class="text-muted">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Credit Balance --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Total Credit Balance</h5>
            </div>
            <div class="card-body">
                <h3 class="text-danger">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($stats['total_balance'], 2) }}</h3>
                <small class="text-muted">Across all faculty accounts</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Recent Transactions --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="{{ route('admin.transactions.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                @if ($recentTransactions->isEmpty())
                    <div class="text-center py-4">
                        <i class="bi bi-receipt text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">No transactions yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction #</th>
                                    <th>Faculty</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentTransactions as $txn)
                                    <tr>
                                        <td>{{ $txn->transaction_date->format('M d, Y H:i') }}</td>
                                        <td><code>{{ $txn->transaction_number }}</code></td>
                                        <td>{{ $txn->faculty->name }} <small class="text-muted">({{ $txn->faculty->faculty_number }})</small></td>
                                        <td>
                                            @foreach ($txn->items as $item)
                                                <div class="small">{{ $item->product_name_snapshot }} × {{ $item->quantity }}</div>
                                            @endforeach
                                        </td>
                                        <td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($txn->total_amount, 2) }}</td>
                                        <td><span class="{{ $txn->status->badgeClass() }}">{{ $txn->status->label() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Faculty Near Credit Limit --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Near Credit Limit</h5>
            </div>
            <div class="card-body p-0">
                @if ($facultyNearLimit->isEmpty())
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">All faculty within limits</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($facultyNearLimit as $f)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $f->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $f->faculty_number }} | {{ $f->department }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger">
                                            {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($f->remaining_credit, 2) }} left
                                        </span>
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