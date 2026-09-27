@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">{{ $faculty->name }}</h1>
        <p class="text-muted mb-0">{{ $faculty->faculty_number }} | {{ $faculty->department }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.faculty.edit', $faculty) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="{{ route('admin.faculty.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row g-4">
    {{-- Credit Summary --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Credit Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Credit Limit</label>
                    <div class="fs-4 fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($balanceDetails['credit_limit'], 2) }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Current Balance</label>
                    <div class="fs-4 fw-bold text-primary">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($balanceDetails['current_balance'], 2) }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Remaining Credit</label>
                    <div class="fs-4 fw-bold {{ $balanceDetails['remaining_credit'] < 200 ? 'text-danger' : 'text-success' }}">
                        {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($balanceDetails['remaining_credit'], 2) }}
                    </div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar {{ $balanceDetails['percentage_used'] > 90 ? 'bg-danger' : ($balanceDetails['percentage_used'] > 70 ? 'bg-warning' : 'bg-success') }}" 
                         role="progressbar" style="width: {{ $balanceDetails['percentage_used'] }}%" 
                         aria-valuenow="{{ $balanceDetails['percentage_used'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-muted small mt-1">{{ $balanceDetails['percentage_used'] }}% used</div>
            </div>
        </div>
    </div>

    {{-- Status & User --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Account Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Status</label>
                    <span class="badge {{ $faculty->is_active ? 'bg-success' : 'bg-secondary' }} fs-6">
                        {{ $faculty->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">User Account</label>
                    @if ($faculty->user)
                        <div>
                            <strong>{{ $faculty->user->username }}</strong>
                            <span class="badge bg-info text-dark ms-2">{{ $faculty->user->role->label() }}</span>
                            <br>
                            <small class="text-muted">{{ $faculty->user->email }}</small>
                        </div>
                    @else
                        <span class="text-muted">No user account linked</span>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Created</label>
                    <div>{{ $faculty->created_at->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('admin.faculty.toggle-status', $faculty) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-{{ $faculty->is_active ? 'warning' : 'success' }}">
                            <i class="bi bi-{{ $faculty->is_active ? 'slash-circle' : 'check-circle' }} me-1"></i>
                            {{ $faculty->is_active ? 'Deactivate' : 'Activate' }} Account
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#creditLimitModal">
                        <i class="bi bi-cash-stack me-1"></i>Change Credit Limit
                    </button>
                    
                    <a href="{{ route('admin.transactions.create') }}?faculty_id={{ $faculty->id }}" class="btn btn-outline-info">
                        <i class="bi bi-plus-circle me-1"></i>Record Transaction
                    </a>
                    
                    <a href="{{ route('admin.transactions.index', ['faculty_id' => $faculty->id]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-list-ul me-1"></i>View All Transactions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- Transaction History -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Transactions</h5>
        <a href="{{ route('admin.transactions.index', ['faculty_id' => $faculty->id]) }}" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        @if ($transactions->isEmpty())
            <div class="text-center py-4">
                <i class="bi bi-receipt text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2">No transactions recorded yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Transaction #</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $txn)
                            <tr>
                                <td>{{ $txn->transaction_date->format('M d, Y H:i') }}</td>
                                <td><code>{{ $txn->transaction_number }}</code></td>
                                <td>
                                    @foreach ($txn->items as $item)
                                        <div class="small">
                                            {{ $item->product_name_snapshot }} × {{ $item->quantity }}
                                            <span class="text-muted">@ {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item->unit_price, 2) }}</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($txn->total_amount, 2) }}</td>
                                <td><span class="{{ $txn->status->badgeClass() }}">{{ $txn->status->label() }}</span></td>
                                <td>{{ $txn->recordedBy->name ?? 'N/A' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.transactions.show', $txn) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Credit Limit Modal -->
<div class="modal fade" id="creditLimitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.faculty.change-credit-limit', $faculty) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Credit Limit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Limit: {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($faculty->credit_limit, 2) }}</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Credit Limit <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ config('app.facu_cred_currency_symbol', 'P') }}</span>
                            <input type="number" name="credit_limit" class="form-control" required min="0" max="50000" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Limit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection