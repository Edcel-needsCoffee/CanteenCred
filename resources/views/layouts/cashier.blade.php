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
</style>
@endsection

@section('content')
<div class="flex min-h-screen" id="wrapper">
    {{-- Sidebar --}}
    <aside class="sidebar-glass" id="sidebar-wrapper">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 glass-emerald rounded-xl flex items-center justify-center">
                    <i class="bi bi-cart text-white text-xl"></i>
                </div>
                <div>
                    <h4 class="text-white font-bold text-lg">{{ config('app.name') }}</h4>
                    <p class="text-emerald-200/80 text-xs">Cashier</p>
                </div>
            </div>
        </div>
        
        <nav class="p-4 space-y-1">
            <a href="{{ route('cashier.pos') }}" class="sidebar-link {{ request()->routeIs('cashier.pos') ? 'active' : '' }}">
                <i class="bi bi-cart text-xl"></i>
                <span>Point of Sale</span>
            </a>
            
            <a href="{{ route('cashier.transactions') }}" class="sidebar-link {{ request()->routeIs('cashier.transactions') ? 'active' : '' }}">
                <i class="bi bi-clock-history text-xl"></i>
                <span>Recent Transactions</span>
            </a>
            
            <a href="{{ route('cashier.faculty-lookup') }}" class="sidebar-link {{ request()->routeIs('cashier.faculty-lookup') ? 'active' : '' }}">
                <i class="bi bi-search text-xl"></i>
                <span>Faculty Lookup</span>
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
    
    document.getElementById('menu-toggle')?.addEventListener('click', function() {
        sidebarCollapsed = !sidebarCollapsed;
        updateSidebarState();
    });
    
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