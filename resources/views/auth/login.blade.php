@extends('layouts.auth')

@section('title', 'Login')

@section('subtitle', 'Sign in to access your account')

@section('content')
<form method="POST" action="{{ route('login') }}" id="login-form">
    @csrf

    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
        @error('username')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-lg" id="login-btn">
            <span class="btn-text">Sign In</span>
            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Signing in...</span>
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
    document.getElementById('login-form')?.addEventListener('submit', function() {
        const btn = document.getElementById('login-btn');
        if (btn) {
            btn.disabled = true;
            btn.querySelector('.btn-text')?.classList.add('d-none');
            btn.querySelector('.btn-loading')?.classList.remove('d-none');
        }
    });
</script>
@endsection