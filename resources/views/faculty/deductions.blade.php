@extends('layouts.faculty')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Deductions</h1>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        @if ($deductions->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No deduction records found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Dates</th>
                            <th>Transactions</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deductions as $deduction)
                            <tr>
                                <td><strong>{{ $deduction->payrollPeriod->label }}</strong></td>
                                <td>
                                    {{ $deduction->payrollPeriod->start_date->format('M d, Y') }} - {{ $deduction->payrollPeriod->end_date->format('M d, Y') }}
                                </td>
                                <td>{{ $deduction->transactions_count }}</td>
                                <td class="fw-bold">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($deduction->total_amount, 2) }}</td>
                                <td><span class="{{ $deduction->status->badgeClass() }}">{{ $deduction->status->label() }}</span></td>
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