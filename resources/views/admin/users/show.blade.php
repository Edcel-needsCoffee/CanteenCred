@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $user->name }}</h1>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">User Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $user->name }}</dd>
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8">{{ $user->username }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $user->email }}</dd>
                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8"><span class="badge bg-info text-dark">{{ $user->role->label() }}</span></dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Faculty</dt>
                    <dd class="col-sm-8">{{ $user->faculty->name ?? '—' }} ({{ $user->faculty->faculty_number ?? '—' }})</dd>
                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                    <dt class="col-sm-4">Last Login</dt>
                    <dd class="col-sm-8">{{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection