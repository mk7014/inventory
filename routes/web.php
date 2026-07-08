<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DarazAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\RequisitionExpenseController;
use App\Http\Controllers\RequisitionItemPurchaseController;
use App\Http\Controllers\RequisitionReviewController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if ($user = auth()->user()) {
        return redirect()->route($user->isAdmin() ? 'dashboard' : 'balance.mine');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Personal profile — any authenticated user manages their own account.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    // Every authenticated user (admin + employee) can see their own balance & costing.
    Route::get('/my-balance', [BalanceController::class, 'mine'])->name('balance.mine');
    Route::get('/my-balance/received', [BalanceController::class, 'received'])->name('balance.received');
    Route::get('/my-balance/spent', [BalanceController::class, 'spent'])->name('balance.spent');
    Route::get('/my-balance/spent/{requisition}', [BalanceController::class, 'spentRequisition'])->name('balance.spent.requisition');
    Route::get('/my-balance/statement', [BalanceController::class, 'statement'])->name('balance.statement');

    // Personal expenses — every authenticated user manages their own (admins see
    // all). Creating one deducts the amount from the user's balance.
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/report', [ExpenseController::class, 'report'])->name('expenses.report');
    Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Everything below is gated by a dynamic, role-assigned permission. Admins
    // bypass every check via the Gate::before hook in AppServiceProvider.

    Route::middleware('permission:dashboard.view')
        ->get('/dashboard', DashboardController::class)->name('dashboard');

    // ── Requisitions ────────────────────────────────────────────────
    Route::middleware('permission:requisitions.create')->group(function () {
        Route::get('/requisitions/create', [RequisitionController::class, 'create'])->name('requisitions.create');
        Route::post('/requisitions', [RequisitionController::class, 'store'])->name('requisitions.store');
        Route::post('/requisitions/{requisition}/items/{item}/purchase', RequisitionItemPurchaseController::class)->name('requisitions.items.purchase');
    });
    Route::middleware('permission:requisitions.approve')
        ->post('/requisitions/{requisition}/review', RequisitionReviewController::class)->name('requisitions.review');
    Route::middleware('permission:requisitions.view')->group(function () {
        Route::get('/requisitions', [RequisitionController::class, 'index'])->name('requisitions.index');
        Route::get('/requisitions/{requisition}', [RequisitionController::class, 'show'])->name('requisitions.show');
    });

    // ── Payments ────────────────────────────────────────────────────
    Route::middleware('permission:payments.view')
        ->get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::middleware('permission:payments.create')
        ->post('/requisitions/{requisition}/payments', [PaymentController::class, 'store'])->name('requisitions.payments.store');

    // ── Sales ───────────────────────────────────────────────────────
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('/sales/stock-check', [SaleController::class, 'stockCheck'])->name('sales.stock-check');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    });
    Route::middleware('permission:sales.create')
        ->post('/sales', [SaleController::class, 'store'])->name('sales.store');

    // ── Returns ─────────────────────────────────────────────────────
    Route::middleware('permission:returns.view')
        ->get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::middleware('permission:returns.create')
        ->post('/returns', [ReturnController::class, 'store'])->name('returns.store');

    // ── Requisition costs (logged against an approved requisition) ──
    Route::middleware('permission:requisitions.create')
        ->post('/requisitions/{requisition}/expenses', [RequisitionExpenseController::class, 'store'])->name('requisitions.expenses.store');

    // ── Products & Stock ────────────────────────────────────────────
    Route::middleware('permission:products.view')
        ->get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('permission:products.create')
        ->post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::middleware('permission:products.update')
        ->put('/products/{product}', [ProductController::class, 'update'])->name('products.update');

    // ── Reports ─────────────────────────────────────────────────────
    Route::middleware('permission:reports.view')
        ->get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::middleware('permission:reports.export')
        ->get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // ── Daraz Accounts ──────────────────────────────────────────────
    Route::middleware('permission:accounts.view')
        ->get('/accounts', [DarazAccountController::class, 'index'])->name('accounts.index');
    Route::middleware('permission:accounts.create')
        ->post('/accounts', [DarazAccountController::class, 'store'])->name('accounts.store');
    Route::middleware('permission:accounts.update')
        ->put('/accounts/{account}', [DarazAccountController::class, 'update'])->name('accounts.update');

    // ── Users ───────────────────────────────────────────────────────
    Route::middleware('permission:users.view')
        ->get('/users', [UserController::class, 'index'])->name('users.index');
    Route::middleware('permission:users.create')
        ->post('/users', [UserController::class, 'store'])->name('users.store');
    Route::middleware('permission:users.update')
        ->put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // ── Roles & Permissions ─────────────────────────────────────────
    Route::middleware('permission:roles.create')->group(function () {
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    });
    Route::middleware('permission:roles.update')->group(function () {
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
    Route::middleware('permission:roles.delete')
        ->delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::middleware('permission:roles.view')
        ->get('/roles', [RoleController::class, 'index'])->name('roles.index');

    // ── Employee Balances (admin overview) ──────────────────────────
    Route::middleware('permission:balances.view')
        ->get('/balances', [BalanceController::class, 'index'])->name('balances.index');
});
