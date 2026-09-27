<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Deductions - {{ $period->label }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .header .period-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 11px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        .summary-value {
            font-weight: bold;
            font-size: 12px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        td {
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .currency {
            font-family: monospace;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-draft { background: #e2e3e5; color: #383d41; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .badge-processed { background: #cce5ff; color: #004085; }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACU-CRED Payroll Deduction Report</h1>
        <div class="period-info">{{ $period->label }} ({{ $period->start_date->format('M d, Y') }} - {{ $period->end_date->format('M d, Y') }})</div>
        <div class="period-info">Generated: {{ now()->format('M d, Y H:i') }}</div>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Total Faculty</div>
            <div class="summary-value">{{ $count }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Amount</div>
            <div class="summary-value currency">{{ $currencySymbol }}{{ number_format($total, 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Period</div>
            <div class="summary-value">{{ $period->label }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%">Faculty #</th>
                <th style="width: 20%">Name</th>
                <th style="width: 18%">Department</th>
                <th style="width: 10%">Transactions</th>
                <th style="width: 15%">Total Amount</th>
                <th style="width: 12%">Status</th>
                <th style="width: 15%">Approved At</th>
                <th style="width: 15%">Processed At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deductions as $deduction)
                <tr>
                    <td>{{ $deduction->faculty->faculty_number }}</td>
                    <td>{{ $deduction->faculty->name }}</td>
                    <td>{{ $deduction->faculty->department }}</td>
                    <td class="text-center">{{ $deduction->transactions_count }}</td>
                    <td class="text-right currency">{{ $currencySymbol }}{{ number_format($deduction->total_amount, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ strtolower($deduction->status->value) }}">{{ $deduction->status->label() }}</span>
                    </td>
                    <td class="text-center">{{ $deduction->approved_at?->format('M d, Y H:i') ?? '—' }}</td>
                    <td class="text-center">{{ $deduction->processed_at?->format('M d, Y H:i') ?? '—' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>Grand Total</strong></td>
                <td class="text-right currency"><strong>{{ $currencySymbol }}{{ number_format($total, 2) }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>FACU-CRED Faculty Canteen Credit and Payroll Deduction System</p>
        <p>This report was generated on {{ now()->format('M d, Y H:i:s') }}</p>
    </div>
</body>
</html>