<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\SaleStatusUpdateRequest;
use App\Http\Requests\SaleStoreRequest;
use App\Models\DarazAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Services\DeletionService;
use App\Services\SaleService;
use App\Services\SaleStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $base = Sale::query();

        // Optional status filter, validated against the lifecycle enum.
        $status = $request->query('status');
        $activeStatus = in_array($status, array_column(SaleStatus::cases(), 'value'), true) ? $status : null;

        $stats = [
            'total_sales'   => (clone $base)->count(),
            'total_revenue' => (clone $base)->where('status', SaleStatus::Delivered->value)->selectRaw('COALESCE(SUM(selling_price * quantity), 0) AS agg')->value('agg'),
            'total_units'   => (clone $base)->sum('quantity'),
            'from_stock'    => (clone $base)->where('source', 'stock')->count(),
            'new_purchase'  => (clone $base)->where('source', 'new_purchase')->count(),
            'booked_units'  => (int) (clone $base)->where('stock_state', 'booked')->sum('booked_quantity'),
            'delivered_units' => (int) (clone $base)->where('status', SaleStatus::Delivered->value)->sum('delivered_quantity'),
            'returned_units'  => (int) (clone $base)->where('status', SaleStatus::Returned->value)->sum('returned_quantity'),
        ];

        $statusCounts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $salesQuery = Sale::query()->with('account', 'product')->latest('sold_date');

        if ($activeStatus !== null) {
            $salesQuery->where('status', $activeStatus);
        }

        return view('sales.index', [
            'sales' => $salesQuery->paginate(15)->withQueryString(),
            'accounts' => DarazAccount::active()->orderBy('account_name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'stats' => $stats,
            'statusCounts' => $statusCounts,
            'activeStatus' => $activeStatus,
        ]);
    }

    public function store(SaleStoreRequest $request, SaleService $service): RedirectResponse
    {
        $service->create($request->validated(), $request->user());

        return back()->with('success', 'Sale recorded.');
    }

    public function updateStatus(SaleStatusUpdateRequest $request, Sale $sale, SaleStatusService $service): RedirectResponse
    {
        // Admins may set any status, so a mistake (a sale a user cancelled by accident,
        // say) can be repaired. Everyone else stays on the state machine's rails.
        $sale = $service->transition(
            $sale,
            $request->validated('status'),
            $request->user(),
            $request->user()->isAdmin(),
        );

        return back()->with('success', 'Sale status updated to '.$sale->statusEnum()->label().'.');
    }

    public function destroy(Sale $sale, DeletionService $service): RedirectResponse
    {
        $service->deleteSale($sale);

        return back()->with('success', 'Sale deleted.');
    }

    public function stockCheck(Request $request)
    {
        $product = Product::findOrFail($request->integer('product_id'));

        $sellable = $product->sellableStock();

        return response()->json([
            'stock' => $product->current_stock,
            'available' => $product->availableStock(),
            'booked' => $product->booked_stock,
            // What a new from-stock sale is actually allowed to take: available minus
            // the units open sales already claim. Mirrors SaleService::assertSellable.
            'sellable' => $sellable,
            'claimed' => $product->availableStock() - $sellable,
            'name' => $product->name,
        ]);
    }
}
