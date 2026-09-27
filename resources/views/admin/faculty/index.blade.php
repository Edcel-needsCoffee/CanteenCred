@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Faculty Accounts</h1>
    <a href="{{ route('admin.faculty.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Faculty
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name or faculty number..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('admin.faculty.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        @if ($faculty->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">No faculty accounts found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Faculty Number</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Credit Limit</th>
                            <th>Balance</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>User</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($faculty as $f)
                            <tr>
                                <td>{{ $f->faculty_number }}</td>
                                <td>{{ $f->name }}</td>
                                <td>{{ $f->department }}</td>
                                <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($f->credit_limit, 2) }}</td>
                                <td>{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($f->balance, 2) }}</td>
                                <td>
                                    <span class="{{ $f->remaining_credit < 200 ? 'text-danger fw-bold' : '' }}">
                                        {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($f->remaining_credit, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $f->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $f->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($f->user)
                                        <span class="badge bg-info text-dark">{{ $f->user->username }} ({{ $f->user->role->label() }})</span>
                                    @else
                                        <span class="text-muted">No user account</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.faculty.show', $f) }}" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.faculty.edit', $f) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.faculty.toggle-status', $f) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-{{ $f->is_active ? 'warning' : 'success' }}" title="{{ $f->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi bi-{{ $f->is_active ? 'slash-circle' : 'check-circle' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $faculty->links() }}
            </div>
        @endif
    </div>
</div>
@endsection