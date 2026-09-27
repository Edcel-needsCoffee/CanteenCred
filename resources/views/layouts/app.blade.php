<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FACU-CRED') }} @yield('title')</title>

    {{-- Fonts --}}
    @fontFace('instrument-sans', 'font', [
        'weight' => [400, 500, 600],
        'style' => 'normal',
        'display' => 'swap',
    ])

    {{-- Scripts --}}
    @vite(['resources/js/app.js'])

    {{-- Styles --}}
    @vite(['resources/css/app.css'])

    @yield('styles')
</head>
<body class="font-sans antialiased min-h-screen bg-emerald-gradient relative overflow-x-hidden">
    {{-- Animated background elements --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl animate-pulse-glow"></div>
        <div class="absolute -bottom-40 -left-40 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl animate-pulse-glow" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/5 rounded-full blur-3xl"></div>
    </div>

    {{-- Page content --}}
    <div class="relative z-10 min-h-screen">
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>