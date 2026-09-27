@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Audit Log Detail</h1>
        <p class="text-muted mb-0">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</p>
    </div>
    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Log Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4">User</dt>
                    <dd class="col-sm-8">
                        @if ($auditLog->user)
                            {{ $auditLog->user->name }} ({{ $auditLog->user->username }})
                        @else
                            System
                        @endif
                    </dd>
                    <dt class="col-sm-4">Action</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-secondary">{{ \App\Enums\AuditAction::tryFrom($auditLog->action)?->label() ?? $auditLog->action }}</span>
                    </dd>
                    <dt class="col-sm-4">Subject</dt>
                    <dd class="col-sm-8">{{ $auditLog->subject_type }} #{{ $auditLog->subject_id }}</dd>
                    <dt class="col-sm-4">Timestamp</dt>
                    <dd class="col-sm-8">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $auditLog->description }}</dd>
                </dl>
            </div>
        </div>

        @if ($auditLog->metadata)
            <hr>
            <h6>Metadata</h6>
            <pre class="bg-light p-3 rounded"><code>{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT) }}</code></pre>
        @endif
    </div>
</div>
@endsection