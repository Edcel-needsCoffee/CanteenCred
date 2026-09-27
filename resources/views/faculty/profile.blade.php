@extends('layouts.faculty')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Profile</h1>
</div>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ auth()->user()->name }}</dd>
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8">{{ auth()->user()->username }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ auth()->user()->email }}</dd>
                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8"><span class="badge bg-info text-dark">{{ auth()->user()->role->label() }}</span></dd>
                    <dt class="col-sm-4">Faculty #</dt>
                    <dd class="col-sm-8">{{ $faculty->faculty_number ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Department</dt>
                    <dd class="col-sm-8">{{ $faculty->department ?? 'N/A' }}</dd>
                    <dt class="col-sm-4">Credit Limit</dt>
                    <dd class="col-sm-8">{{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($faculty->credit_limit ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-key me-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection