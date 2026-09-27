@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Transaction Corrections</h1>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="transaction_id" class="form-control" placeholder="Transaction #" value="{{ request('transaction_id') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('admin.corrections.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if ($corrections->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-pencil-square text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No correction requests found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Transaction</th>
                            <th>Faculty</th>
                            <th>Requested By</th>
                            <th>Original Total</th>
                            <th>Corrected Total</th>
                            <th>Difference</th>
                            <th>Status</th>
                            <th>Requested At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($corrections as $correction)
                            <tr>
                                <td><code>{{ $correction->id }}</code></td>
                                <td><code>{{ $correction->transaction->transaction_number }}</code></td>
                                <td>{{ $correction->transaction->faculty->name }} ({{ $correction->transaction->faculty->faculty_number }})</td>
                                <td>{{ $correction->requestedBy->name }}</td>
                                <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->original_total, 2) }}</td>
                                <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->corrected_total, 2) }}</td>
                                <td class="{{ $correction->difference >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                    {{ $correction->difference >= 0 ? '+' : '' }}{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->difference, 2) }}
                                </td>
                                <td><span class="{{ $correction->status->badgeClass() }}">{{ $correction->status->label() }}</span></td>
                                <td>{{ $correction->created_at->format('M d, Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.corrections.show', $correction) }}" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($correction->isPending())
                                            <form action="{{ route('admin.corrections.approve', $correction) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Approve" onclick="return confirm('Approve this correction?');">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.corrections.reject', $correction) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger" title="Reject" onclick="return confirm('Reject this correction?');">
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
                {{ $corrections->links() }}
            </div>
        @endif
    </div>
</div>
@endsection