@extends('layouts.faculty')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Transactions</h1>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
                    <option value="corrected" {{ request('status') == 'corrected' ? 'selected' : '' }}>Corrected</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-grow-1">Filter</button>
                <a href="{{ route('faculty.transactions') }}" class="btn btn-outline-secondary">Clear</a>
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
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
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