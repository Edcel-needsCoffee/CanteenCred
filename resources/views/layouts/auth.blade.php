<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FACU-CRED') }} - @yield('title')</title>

    @fontFace('instrument-sans', 'font', [
        'weight' => [400, 500, 600],
        'style' => 'normal',
        'display' => 'swap',
    ])

    @vite(['resources/css/app.css'])
    @vite(['resources/js/app.js'])

    @yield('styles')
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">{{ config('app.name') }}</h2>
                            <p class="text-muted">@yield('subtitle')</p>
                        </div>

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </div>
                <p class="text-center text-muted small mt-3">
                    Faculty Canteen Credit and Payroll Deduction System
                </p>
            </div>
        </div>
    </div>

    @yield('scripts')
</body>
</html>