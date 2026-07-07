<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DarazAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\RequisitionExpenseController;
use App\Http\Controllers\RequisitionItemPurchaseController;
use App\Http\Controllers\RequisitionReviewController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('requisitions', RequisitionController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/sales/stock-check', [SaleController::class, 'stockCheck'])->name('sales.stock-check');
    Route::resource('sales', SaleController::class)->only(['index', 'store']);
    Route::resource('returns', ReturnController::class)->only(['index', 'store']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update']);
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/expenses', [RequisitionExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/requisitions/{requisition}/expenses', [RequisitionExpenseController::class, 'store'])->name('requisitions.expenses.store');

    Route::get('/my-balance', [BalanceController::class, 'mine'])->name('balance.mine');
    Route::get('/my-balance/received', [BalanceController::class, 'received'])->name('balance.received');
    Route::get('/my-balance/spent', [BalanceController::class, 'spent'])->name('balance.spent');
    Route::get('/my-balance/statement', [BalanceController::class, 'statement'])->name('balance.statement');
    Route::post('/requisitions/{requisition}/items/{item}/purchase', RequisitionItemPurchaseController::class)->name('requisitions.items.purchase');

    Route::middleware('role:admin')->group(function () {
        Route::post('/requisitions/{requisition}/review', RequisitionReviewController::class)->name('requisitions.review');
        Route::post('/requisitions/{requisition}/payments', [PaymentController::class, 'store'])->name('requisitions.payments.store');
        Route::resource('accounts', DarazAccountController::class)->only(['index', 'store', 'update']);
        Route::resource('users', UserController::class)->only(['index', 'store', 'update']);
        Route::get('/balances', [BalanceController::class, 'index'])->name('balances.index');
    });
});
