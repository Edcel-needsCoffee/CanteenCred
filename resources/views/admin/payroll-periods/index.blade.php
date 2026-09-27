@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Payroll Periods</h1>
    <a href="{{ route('admin.payroll-periods.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Period
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        @if ($periods->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-calendar text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No payroll periods found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Label</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Deductions</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($periods as $period)
                            <tr>
                                <td>{{ $period->label }}</td>
                                <td>{{ $period->start_date->format('M d, Y') }}</td>
                                <td>{{ $period->end_date->format('M d, Y') }}</td>
                                <td><span class="{{ $period->status->badgeClass() }}">{{ $period->status->label() }}</span></td>
                                <td>{{ $period->deductions_count }}</td>
                                <td>{{ $period->created_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.payroll-periods.show', $period) }}" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($period->isOpen())
                                            <a href="{{ route('admin.payroll-periods.edit', $period) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.payroll-periods.close', $period) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Close" onclick="return confirm('Close this period? No new transactions can be added.');">
                                                    <i class="bi bi-lock"></i>
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
                {{ $periods->links() }}
            </div>
        @endif
    </div>
</div>
@endsection