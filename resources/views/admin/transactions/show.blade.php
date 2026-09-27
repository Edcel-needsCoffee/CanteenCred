@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Transaction {{ $transaction->transaction_number }}</h1>
        <p class="text-muted mb-0">{{ $transaction->faculty->faculty_number }} - {{ $transaction->faculty->name }}</p>
    </div>
    <div class="d-flex gap-2">
        @if ($transaction->canBeVoided())
            <form action="{{ route('admin.transactions.void', $transaction) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to void this transaction?');">
                    <i class="bi bi-x-circle me-1"></i>Void
                </button>
            </form>
        @endif
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary">
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
                <h5 class="mb-0">Transaction Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Transaction Number</label>
                    <code class="fs-6">{{ $transaction->transaction_number }}</code>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Date & Time</label>
                    <div>{{ $transaction->transaction_date->format('M d, Y H:i:s') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Faculty</label>
                    <div>
                        <strong>{{ $transaction->faculty->name }}</strong>
                        <br>
                        <small class="text-muted">{{ $transaction->faculty->faculty_number }} | {{ $transaction->faculty->department }}</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Total Amount</label>
                    <div class="fs-3 fw-bold text-primary">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($transaction->total_amount, 2) }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Status</label>
                    <span class="{{ $transaction->status->badgeClass() }} fs-6">{{ $transaction->status->label() }}</span>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Recorded By</label>
                    <div>{{ $transaction->recordedBy->name ?? 'N/A' }}</div>
                </div>
                @if ($transaction->credit_limit_override)
                    <div class="alert alert-warning">
                        <strong>Credit Limit Override</strong>
                        <br>
                        <small>{{ $transaction->override_reason }}</small>
                        <br>
                        <small class="text-muted">Authorized by: {{ $transaction->overrideAuthorizedBy->name ?? 'N/A' }}</small>
                    </div>
                @endif
                @if ($transaction->payroll_deduction_id)
                    <div class="alert alert-info">
                        <strong>Linked to Payroll Deduction</strong>
                        <br>
                        <small>Cannot be voided or corrected</small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Line Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaction->items as $item)
                                <tr>
                                    <td>{{ $item->product_name_snapshot }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-active">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold fs-5">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($transaction->total_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection