@extends('layouts.app')

@section('content')
<div class="d-flex" id="wrapper">
    {{-- Sidebar --}}
    <div class="bg-dark border-end" id="sidebar-wrapper" style="width: 250px; min-height: 100vh;">
        <div class="sidebar-heading border-bottom py-3 px-3">
            <h5 class="text-white mb-0 fw-bold">{{ config('app.name') }}</h5>
            <small class="text-muted">Payroll / Finance</small>
        </div>
        <div class="list-group list-group-flush">
            <a href="{{ route('payroll.dashboard') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 {{ request()->routeIs('payroll.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            <a href="{{ route('payroll.deductions.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 {{ request()->routeIs('payroll.deductions.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text me-2"></i>Approved Deductions
            </a>
            {{-- <a href="{{ route('payroll.exports.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 {{ request()->routeIs('payroll.exports.*') ? 'active' : '' }}">
                <i class="bi bi-download me-2"></i>Export
            </a> --}}
        </div>
    </div>

    {{-- Page Content --}}
    <div id="page-content-wrapper" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <div class="container-fluid">
                <button class="btn btn-link text-dark" id="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="text-muted small">{{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-link text-dark p-0">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <div class="container-fluid py-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('menu-toggle')?.addEventListener('click', function() {
        document.getElementById('wrapper')?.classList.toggle('toggled');
    });
</script>
@yield('page-scripts')
@endsection