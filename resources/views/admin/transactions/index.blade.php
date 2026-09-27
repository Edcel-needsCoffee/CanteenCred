@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Transactions</h1>
    <a href="{{ route('admin.transactions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Record Transaction
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="faculty_id" class="form-select">
                    <option value="">All Faculty</option>
                    @foreach ($facultyList as $f)
                        <option value="{{ $f->id }}" {{ request('faculty_id') == $f->id ? 'selected' : '' }}>
                            {{ $f->faculty_number }} - {{ $f->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
                    <option value="corrected" {{ request('status') == 'corrected' ? 'selected' : '' }}>Corrected</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if ($transactions->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No transactions found.</p>
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
                                    {{ $txn->faculty->faculty_number }} - {{ $txn->faculty->name }}
                                </td>
                                <td>
                                    @foreach ($txn->items as $item)
                                        <div class="small">
                                            {{ $item->product_name_snapshot }} × {{ $item->quantity }}
                                        </div>
                                    @endforeach
                                </td>
                                <td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($txn->total_amount, 2) }}</td>
                                <td><span class="{{ $txn->status->badgeClass() }}">{{ $txn->status->label() }}</span></td>
                                <td>{{ $txn->recordedBy->name ?? 'N/A' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.transactions.show', $txn) }}" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($txn->canBeVoided())
                                            <form action="{{ route('admin.transactions.void', $txn) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger" title="Void" onclick="return confirm('Are you sure you want to void this transaction?');">
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
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection