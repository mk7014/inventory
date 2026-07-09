<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleStoreRequest;
use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $base = Sale::query();

        $stats = [
            'total_sales'   => (clone $base)->count(),
            'total_revenue' => (clone $base)->selectRaw('COALESCE(SUM(selling_price * quantity), 0) AS agg')->value('agg'),
            'total_units'   => (clone $base)->sum('quantity'),
            'from_stock'    => (clone $base)->where('source', 'stock')->count(),
            'new_purchase'  => (clone $base)->where('source', 'new_purchase')->count(),
        ];

        return view('sales.index', [
            'sales' => Sale::query()->with('account', 'product')->latest('sold_date')->paginate(15),
            'accounts' => DarazAccount::active()->orderBy('account_name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function store(SaleStoreRequest $request, SaleService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return back()->with('success', 'Sale recorded.');
    }

    public function stockCheck(Request $request)
    {
        $product = Product::findOrFail($request->integer('product_id'));

        return response()->json(['stock' => $product->current_stock, 'name' => $product->name]);
    }
}
