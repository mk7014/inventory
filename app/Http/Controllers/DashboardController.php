<?php

namespace App\Http\Controllers;

use App\Models\DarazAccount;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Requisition;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $requisitionQuery = Requisition::query();
        if (! $user->isAdmin()) {
            $requisitionQuery->where('employee_id', $user->id);
        }

        $summary = [
            'pending_amount' => (clone $requisitionQuery)->where('status', 'pending')->whereBetween('requested_at', [$monthStart, $monthEnd])->sum('total_amount'),
            'pending_count' => (clone $requisitionQuery)->where('status', 'pending')->count(),
            'paid_amount' => Payment::query()->whereBetween('payment_date', [$monthStart, $monthEnd])->sum('amount'),
            'sales_revenue' => Sale::query()->where('status', 'delivered')->whereBetween('sold_date', [$monthStart, $monthEnd])->selectRaw('COALESCE(SUM(selling_price * quantity), 0) as total')->value('total'),
            'stock_count' => Product::query()->sum('current_stock'),
            'returns_count' => ProductReturn::query()->whereBetween('return_date', [$monthStart, $monthEnd])->count(),
        ];
        $summary['net_profit'] = $summary['sales_revenue'] - $summary['paid_amount'];

        $accounts = DarazAccount::query()
            ->withCount(['sales as completed_sales_count' => fn ($query) => $query->where('status', 'delivered')])
            ->orderBy('account_name')
            ->get();

        $recentRequisitions = (clone $requisitionQuery)->with('employee')->latest()->limit(8)->get();
        $lowStockProducts = Product::query()->where('current_stock', '<=', 3)->orderBy('current_stock')->limit(8)->get();
        $statusChart = Requisition::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dashboard.index', compact('summary', 'accounts', 'recentRequisitions', 'lowStockProducts', 'statusChart'));
    }
}
