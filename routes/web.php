<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DarazAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectPurchaseApprovalController;
use App\Http\Controllers\DirectPurchaseController;
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
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
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

    // ── Direct Purchases ────────────────────────────────────────────
    Route::middleware('permission:direct_purchases.create')->group(function () {
        Route::get('/direct-purchases/create', [DirectPurchaseController::class, 'create'])->name('direct-purchases.create');
        Route::post('/direct-purchases', [DirectPurchaseController::class, 'store'])->name('direct-purchases.store');
    });
    Route::middleware('permission:direct_purchases.approve')->group(function () {
        Route::post('/direct-purchases/{purchase}/review', DirectPurchaseApprovalController::class)->name('direct-purchases.review');
    });
    Route::middleware('permission:direct_purchases.view')->group(function () {
        Route::get('/direct-purchases', [DirectPurchaseController::class, 'index'])->name('direct-purchases.index');
        Route::get('/direct-purchases/{purchase}', [DirectPurchaseController::class, 'show'])->name('direct-purchases.show');
    });

    // ── Sales ───────────────────────────────────────────────────────
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('/sales/stock-check', [SaleController::class, 'stockCheck'])->name('sales.stock-check');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    });
    Route::middleware('permission:sales.create')
        ->post('/sales', [SaleController::class, 'store'])->name('sales.store');
    // Status update is open to anyone who can view sales (not gated on sales.update).
    Route::middleware('permission:sales.view')
        ->patch('/sales/{sale}/status', [SaleController::class, 'updateStatus'])->name('sales.status.update');

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

    // ── Stock Adjustments (manual increase / decrease) ──────────────
    // Append-only: a mistake is corrected with an opposite adjustment, never by
    // editing or deleting a past one, so the stock ledger stays auditable.
    Route::middleware('permission:stock_adjustments.view')
        ->get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::middleware('permission:stock_adjustments.create')
        ->post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');

    // ── Reports ─────────────────────────────────────────────────────
    Route::middleware('permission:reports.view')
        ->get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::middleware('permission:reports.export')
        ->get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // ── Suppliers ───────────────────────────────────────────────────
    Route::middleware('permission:suppliers.view')
        ->get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::middleware('permission:suppliers.create')
        ->post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::middleware('permission:suppliers.update')
        ->put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

    // ── Warehouses ──────────────────────────────────────────────────
    Route::middleware('permission:warehouses.view')
        ->get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::middleware('permission:warehouses.create')
        ->post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::middleware('permission:warehouses.update')
        ->put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');

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

    // ── Admin-only hard delete (dummy/test data cleanup) ────────────
    // Force-deletes a record with all of its child rows and ledger entries.
    // Restricted to the admin role — it does not reverse stock/balance effects.
    Route::middleware('role:admin')->group(function () {
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/accounts/{account}', [DarazAccountController::class, 'destroy'])->name('accounts.destroy');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
        Route::delete('/requisitions/{requisition}', [RequisitionController::class, 'destroy'])->name('requisitions.destroy');
        Route::delete('/direct-purchases/{purchase}', [DirectPurchaseController::class, 'destroy'])->name('direct-purchases.destroy');
    });
});
