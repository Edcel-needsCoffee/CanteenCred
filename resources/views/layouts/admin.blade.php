@extends('layouts.app')

@section('styles')
<style>
    .sidebar-glass {
        @apply glass-strong fixed inset-y-0 left-0 z-50 w-64 transition-all duration-300;
    }
    
    .sidebar-glass.collapsed {
        @apply w-20;
    }
    
    .sidebar-link {
        @apply flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white hover:bg-emerald-500/20 transition-all duration-200 rounded-none border-l-4 border-transparent hover:border-emerald-400;
    }
    
    .sidebar-link.active {
        @apply text-white bg-emerald-500/20 border-emerald-400;
    }
    
    .nav-item {
        @apply relative;
    }
    
    .nav-badge {
        @apply absolute -top-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center;
    }
    
    .content-wrapper {
        @apply transition-all duration-300;
    }
    
    .content-wrapper.shifted {
        margin-left: 16rem;
    }
    
    .content-wrapper.shifted-collapsed {
        margin-left: 5rem;
    }
</style>
@endsection

@section('content')
<div class="flex min-h-screen" id="wrapper">
    {{-- Sidebar --}}
    <aside class="sidebar-glass" id="sidebar-wrapper">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 glass-emerald rounded-xl flex items-center justify-center">
                    <i class="bi bi-credit-card text-white text-xl"></i>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg">{{ config('app.name') }}</h4>
                    <p class="text-emerald-200/80 text-xs">Administrator</p>
                </div>
            </div>
        </div>
        
        <nav class="p-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 text-xl"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="{{ route('admin.pos') }}" class="sidebar-link {{ request()->routeIs('admin.pos') ? 'active' : '' }}">
                <i class="bi bi-cart text-xl"></i>
                <span>Point of Sale</span>
            </a>
            
            <hr class="border-white/10 my-4">
            
            <a href="{{ route('admin.faculty.index') }}" class="sidebar-link {{ request()->routeIs('admin.faculty.*') ? 'active' : '' }}">
                <i class="bi bi-people text-xl"></i>
                <span>Faculty Accounts</span>
            </a>
            
            <a href="{{ route('admin.products.index') }}" class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box text-xl"></i>
                <span>Products / Menu</span>
            </a>
            
            <a href="{{ route('admin.transactions.index') }}" class="sidebar-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                <i class="bi bi-receipt text-xl"></i>
                <span>Transactions</span>
            </a>
            
            <a href="{{ route('admin.corrections.index') }}" class="sidebar-link {{ request()->routeIs('admin.corrections.*') ? 'active' : '' }}">
                <i class="bi bi-pencil-square text-xl"></i>
                <span>Corrections</span>
            </a>
            
            <hr class="border-white/10 my-4">
            
            <a href="{{ route('admin.payroll-periods.index') }}" class="sidebar-link {{ request()->routeIs('admin.payroll-periods.*') ? 'active' : '' }}">
                <i class="bi bi-calendar text-xl"></i>
                <span>Payroll Periods</span>
            </a>
            
            <a href="{{ route('admin.deductions.index') }}" class="sidebar-link {{ request()->routeIs('admin.deductions.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text text-xl"></i>
                <span>Payroll Deductions</span>
            </a>
            
            <hr class="border-white/10 my-4">
            
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear text-xl"></i>
                <span>Users</span>
            </a>
            
            <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text text-xl"></i>
                <span>Audit Logs</span>
            </a>
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 min-h-screen" :class="sidebarCollapsed ? 'content-wrapper shifted-collapsed' : 'content-wrapper shifted'">
        {{-- Top Navbar --}}
        <header class="navbar-glass sticky top-0 z-40">
            <div class="flex items-center justify-between px-6 py-4">
                <button 
                    id="menu-toggle" 
                    class="glass px-3 py-2 rounded-xl text-emerald-700 hover:bg-emerald-500/10 transition-all duration-200"
                    aria-label="Toggle sidebar"
                >
                    <i class="bi bi-list text-xl"></i>
                </button>
                
                <div class="flex items-center gap-4">
                    <div class="hidden md:block glass px-4 py-2 rounded-xl text-right">
                        <p class="text-emerald-800 font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-emerald-600">{{ auth()->user()->role->label() }}</p>
                    </div>
                    
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="glass px-4 py-2 rounded-xl text-emerald-700 hover:bg-emerald-500/10 transition-all duration-200 flex items-center gap-2">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <div class="p-6 md:p-8 animate-fade-in">
            @if (session('success'))
                <div class="alert-emerald-glass-success mb-6 animate-slide-in-right" role="alert">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-check-circle-fill text-xl"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert-emerald-glass-error mb-6 animate-slide-in-right" role="alert">
                    <div class="flex items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @yield('page-header')

            @yield('content')
        </div>
    </main>
</div>

@endsection

@section('scripts')
<script>
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar-wrapper');
    const wrapper = document.getElementById('wrapper');
    const menuToggle = document.getElementById('menu-toggle');
    
    let sidebarCollapsed = false;
    
    function updateSidebarState() {
        if (sidebarCollapsed) {
            document.getElementById('sidebar-wrapper')?.classList.add('collapsed');
            wrapper?.classList.remove('shifted');
            wrapper?.classList.add('shifted-collapsed');
        } else {
            document.getElementById('sidebar-wrapper')?.classList.remove('collapsed');
            wrapper?.classList.add('shifted');
            wrapper?.classList.remove('shifted-collapsed');
        }
    }
    
    menuToggle?.addEventListener('click', function() {
        sidebarCollapsed = !sidebarCollapsed;
        updateSidebarState();
    });
    
    // Initialize
    updateSidebarState();
    
    // Auto-collapse on mobile
    function handleResize() {
        if (window.innerWidth < 768) {
            sidebarCollapsed = true;
        } else {
            sidebarCollapsed = false;
        }
        updateSidebarState();
    }
    
    window.addEventListener('resize', handleResize);
    handleResize();
</script>
@yield('page-scripts')
@endsection