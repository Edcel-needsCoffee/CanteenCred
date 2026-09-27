@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Payroll Period</h1>
    <a href="{{ route('admin.payroll-periods.show', $payrollPeriod) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Details
    </a>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Period Information</h5>
    </div>
    <div class="card-body">
        @if (!$payrollPeriod->isOpen())
            <div class="alert alert-warning">
                This period is {{ $payrollPeriod->status->label() }} and cannot be edited.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.payroll-periods.update', $payrollPeriod) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Label <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" 
                           value="{{ old('label', $payrollPeriod->label) }}" required maxlength="100">
                    @error('label')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                           value="{{ old('start_date', $payrollPeriod->start_date->format('Y-m-d')) }}" required>
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                           value="{{ old('end_date', $payrollPeriod->end_date->format('Y-m-d')) }}" required>
                    @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" {{ !$payrollPeriod->isOpen() ? 'disabled' : '' }}>
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
                <a href="{{ route('admin.payroll-periods.show', $payrollPeriod) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection