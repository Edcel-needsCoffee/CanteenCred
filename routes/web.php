<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

// Public routes
Route::get('/', fn() => redirect()->route('login'));

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
    // Administrator routes
    Route::middleware(['role:administrator'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/pos', [\App\Http\Controllers\Cashier\POSController::class, 'index'])->name('pos');
        
        // Faculty management
        Route::resource('faculty', \App\Http\Controllers\Admin\FacultyController::class);
        
        // Product management
        Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);
        
        // Transactions
        Route::resource('transactions', \App\Http\Controllers\Admin\TransactionController::class);
        
        // Corrections
        Route::resource('corrections', \App\Http\Controllers\Admin\CorrectionController::class);
        
        // Payroll Periods
        Route::resource('payroll-periods', \App\Http\Controllers\Admin\PayrollPeriodController::class);
        
        // Payroll Deductions
        Route::resource('deductions', \App\Http\Controllers\Admin\DeductionController::class);
        Route::get('deductions/{payrollPeriod}/export/pdf', [\App\Http\Controllers\Admin\DeductionController::class, 'exportPdf'])
            ->name('admin.deductions.export.pdf')
            ->middleware('role:administrator');

        // Users
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        
        // Audit Logs
        Route::resource('audit-logs', \App\Http\Controllers\Admin\AuditLogController::class)->only(['index', 'show']);
        
        // Reports
        Route::get('/reports', fn() => view('admin.reports'))->name('reports');
        
        // Settings
        Route::get('/settings', fn() => view('admin.settings'))->name('settings');
    });

    // Cashier routes
    Route::middleware(['role:cashier,administrator'])->prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/pos', [\App\Http\Controllers\Cashier\POSController::class, 'index'])->name('pos');
        Route::get('/transactions', [\App\Http\Controllers\Cashier\TransactionController::class, 'index'])->name('transactions');
        Route::get('/faculty-lookup', fn() => view('cashier.faculty-lookup'))->name('faculty-lookup');
    });

    // Payroll routes
    Route::middleware(['role:payroll,administrator'])->prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/dashboard', fn() => view('payroll.dashboard'))->name('dashboard');
        Route::resource('deductions', \App\Http\Controllers\Payroll\DeductionController::class)->only(['index', 'show']);
        Route::get('/exports', fn() => view('payroll.exports'))->name('exports');
    });

    // Faculty self-service routes
    Route::middleware(['role:faculty'])->prefix('faculty')->name('faculty.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Faculty\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/transactions', [\App\Http\Controllers\Faculty\TransactionController::class, 'index'])->name('transactions');
        Route::get('/deductions', [\App\Http\Controllers\Faculty\DeductionController::class, 'index'])->name('deductions');
        Route::get('/profile', fn() => view('faculty.profile'))->name('profile');
    });
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->group(function () {
    // Auth
    Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/user', [\App\Http\Controllers\Api\AuthController::class, 'user'])->name('api.auth.user')->middleware('auth:sanctum');

    // Faculty search (must come before apiResource to avoid route conflict)
    Route::get('faculty/search', [\App\Http\Controllers\Api\FacultyController::class, 'search'])
        ->middleware('role:administrator,cashier');
    Route::get('faculty/{faculty}/balance', [\App\Http\Controllers\Api\FacultyController::class, 'balance'])
        ->middleware('role:administrator,cashier');

    // Users
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->only(['index', 'show', 'store', 'update', 'destroy'])
        ->middleware('role:administrator');

    // Faculty
    Route::apiResource('faculty', \App\Http\Controllers\Api\FacultyController::class)
        ->middleware('role:administrator,cashier');
    Route::get('faculty/{faculty_id}/transactions', [\App\Http\Controllers\Api\TransactionController::class, 'facultyTransactions'])
        ->middleware('role:administrator,cashier');

    // Products
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class)
        ->middleware('role:administrator,cashier');

    // Transactions
    Route::apiResource('transactions', \App\Http\Controllers\Api\TransactionController::class)
        ->middleware('role:administrator,cashier');
    Route::get('faculty/{faculty_id}/transactions', [\App\Http\Controllers\Api\TransactionController::class, 'facultyTransactions'])
        ->middleware('role:administrator,cashier');
    Route::patch('transactions/{transaction}/void', [\App\Http\Controllers\Api\TransactionController::class, 'void'])
        ->middleware('role:administrator');

    // Corrections
    Route::apiResource('transaction-corrections', \App\Http\Controllers\Api\CorrectionController::class)
        ->middleware('role:administrator,cashier');
    Route::patch('transaction-corrections/{id}/approve', [\App\Http\Controllers\Api\CorrectionController::class, 'approve'])
        ->middleware('role:administrator');
    Route::patch('transaction-corrections/{id}/reject', [\App\Http\Controllers\Api\CorrectionController::class, 'reject'])
        ->middleware('role:administrator');

    // Payroll Periods
    Route::apiResource('payroll-periods', \App\Http\Controllers\Api\PayrollPeriodController::class)
        ->middleware('role:administrator,payroll');
    Route::patch('payroll-periods/{id}/close', [\App\Http\Controllers\Api\PayrollPeriodController::class, 'close'])
        ->middleware('role:administrator');

    // Payroll Deductions
    Route::get('payroll-periods/{id}/deductions', [\App\Http\Controllers\Api\DeductionController::class, 'index'])
        ->middleware('role:administrator,payroll');
    Route::get('payroll-deductions/{id}', [\App\Http\Controllers\Api\DeductionController::class, 'show'])
        ->middleware('role:administrator,payroll');
    Route::post('payroll-periods/{id}/generate-deductions', [\App\Http\Controllers\Api\DeductionController::class, 'generate'])
        ->middleware('role:administrator');
    Route::patch('payroll-deductions/{id}/approve', [\App\Http\Controllers\Api\DeductionController::class, 'approve'])
        ->middleware('role:administrator');
    Route::patch('payroll-deductions/{id}/reject', [\App\Http\Controllers\Api\DeductionController::class, 'reject'])
        ->middleware('role:administrator');
    Route::patch('payroll-deductions/{id}/processed', [\App\Http\Controllers\Api\DeductionController::class, 'markProcessed'])
        ->middleware('role:payroll,administrator');
    Route::get('payroll-periods/{id}/export', [\App\Http\Controllers\Api\DeductionController::class, 'export'])
        ->middleware('role:payroll,administrator');
    Route::get('payroll-periods/{id}/export/pdf', [\App\Http\Controllers\Api\DeductionController::class, 'exportPdf'])
        ->middleware('role:payroll,administrator');

    // Faculty Self-Service
    Route::get('faculty/me', [\App\Http\Controllers\Api\FacultyController::class, 'me'])
        ->middleware('role:faculty');
    Route::get('faculty/me/balance', [\App\Http\Controllers\Api\FacultyController::class, 'myBalance'])
        ->middleware('role:faculty');
    Route::get('faculty/me/transactions', [\App\Http\Controllers\Api\FacultyController::class, 'myTransactions'])
        ->middleware('role:faculty');
    Route::get('faculty/me/deductions', [\App\Http\Controllers\Api\FacultyController::class, 'myDeductions'])
        ->middleware('role:faculty');

    // Audit Logs
    Route::apiResource('audit-logs', \App\Http\Controllers\Api\AuditLogController::class)->only(['index', 'show'])
        ->middleware('role:administrator');
});