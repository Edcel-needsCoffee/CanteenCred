@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Faculty Account</h1>
    <a href="{{ route('admin.faculty.show', $faculty) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Details
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Faculty Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.faculty.update', $faculty) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Faculty Number</label>
                    <input type="text" class="form-control" value="{{ $faculty->faculty_number }}" readonly>
                    <div class="form-text">Faculty number cannot be changed.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $faculty->name) }}" required maxlength="255">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" 
                           value="{{ old('department', $faculty->department) }}" required maxlength="255" list="departments">
                    <datalist id="departments">
                        @foreach ($departments ?? [] as $dept)
                            <option value="{{ $dept }}">
                        @endforeach
                    </datalist>
                    @error('department')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Credit Limit <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">{{ config('app.facu_cred_currency_symbol', 'P') }}</span>
                        <input type="number" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" 
                               value="{{ old('credit_limit', $faculty->credit_limit) }}" required min="0" max="50000" step="0.01">
                    </div>
                    @error('credit_limit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Maximum: {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format(config('app.facu_cred_max_credit_limit', 50000), 2) }}</div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', $faculty->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            @if (!$faculty->user)
                <h5 class="mb-3">Create User Account</h5>
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="create_user" class="form-check-input" id="create_user" value="1">
                    <label class="form-check-label" for="create_user">Create login account for this faculty member</label>
                </div>

                <div id="user_fields" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                                   value="{{ old('username') }}" maxlength="50">
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email') }}" maxlength="255">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                                   minlength="8">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <strong>Linked User Account:</strong> {{ $faculty->user->username }} ({{ $faculty->user->role->label() }})
                    <br>
                    <small class="text-muted">User account management is handled separately in the Users section.</small>
                </div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
                <a href="{{ route('admin.faculty.show', $faculty) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    document.getElementById('create_user')?.addEventListener('change', function() {
        document.getElementById('user_fields').style.display = this.checked ? 'block' : 'none';
    });
</script>
@endsection