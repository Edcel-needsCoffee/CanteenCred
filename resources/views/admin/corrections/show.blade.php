@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Correction Request #{{ $correction->id }}</h1>
        <p class="text-muted mb-0">Transaction: {{ $correction->transaction->transaction_number }}</p>
    </div>
    <a href="{{ route('admin.corrections.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
</div>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Request Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Status</label>
                    <span class="{{ $correction->status->badgeClass() }} fs-6">{{ $correction->status->label() }}</span>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Transaction</label>
                    <code>{{ $correction->transaction->transaction_number }}</code>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Faculty</label>
                    {{ $correction->transaction->faculty->name }} ({{ $correction->transaction->faculty->faculty_number }})
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Requested By</label>
                    {{ $correction->requestedBy->name }}
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Requested At</label>
                    {{ $correction->created_at->format('M d, Y H:i:s') }}
                </div>
                @if ($correction->isApproved())
                    <div class="mb-3">
                        <label class="form-label text-muted small">Approved By</label>
                        {{ $correction->approvedBy->name }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Approved At</label>
                        {{ $correction->approved_at->format('M d, Y H:i:s') }}
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label text-muted small">Reason</label>
                    <p>{{ $correction->reason }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaction Comparison</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Original Transaction</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Field</th><th>Value</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Total Amount</td><td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->original_snapshot['transaction']['total_amount'], 2) }}</td></tr>
                                <tr><td>Status</td><td>{{ $correction->original_snapshot['transaction']['status'] }}</td></tr>
                                <tr><td>Override</td><td>{{ $correction->original_snapshot['transaction']['credit_limit_override'] ? 'Yes' : 'No' }}</td></tr>
                            </tbody>
                        </table>
                        
                        <h6 class="text-muted mt-3">Original Items</h6>
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                @foreach ($correction->original_snapshot['items'] as $item)
                                    <tr>
                                        <td>{{ $item['product_name_snapshot'] }}</td>
                                        <td>{{ $item['quantity'] }}</td>
                                        <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item['unit_price'], 2) }}</td>
                                        <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item['subtotal'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Corrected Transaction</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Field</th><th>Value</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Total Amount</td><td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->corrected_snapshot['transaction']['total_amount'], 2) }}</td></tr>
                                <tr><td>Status</td><td>{{ $correction->corrected_snapshot['transaction']['status'] }}</td></tr>
                                <tr><td>Override</td><td>{{ $correction->corrected_snapshot['transaction']['credit_limit_override'] ? 'Yes' : 'No' }}</td></tr>
                            </tbody>
                        </table>
                        
                        <h6 class="text-muted mt-3">Corrected Items</h6>
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                @foreach ($correction->corrected_snapshot['items'] as $item)
                                    <tr>
                                        <td>{{ $item['product_name_snapshot'] }}</td>
                                        <td>{{ $item['quantity'] }}</td>
                                        <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item['unit_price'], 2) }}</td>
                                        <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item['subtotal'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <hr>
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Original Total</div>
                            <div class="fs-4 fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->original_total, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Corrected Total</div>
                            <div class="fs-4 fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->corrected_total, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-{{ $correction->difference >= 0 ? 'success' : 'danger' }} bg-opacity-10 rounded">
                            <div class="text-muted small">Difference</div>
                            <div class="fs-4 fw-bold text-{{ $correction->difference >= 0 ? 'success' : 'danger' }}">
                                {{ $correction->difference >= 0 ? '+' : '' }}{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($correction->difference, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection