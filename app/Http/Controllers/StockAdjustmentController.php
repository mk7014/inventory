<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentStoreRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'product_id' => $request->input('product_id'),
            'type' => $request->input('type'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $query = StockAdjustment::query()
            ->with(['product', 'creator'])
            ->when($filters['product_id'], fn ($q, $id) => $q->where('product_id', $id))
            ->when(in_array($filters['type'], ['increase', 'decrease'], true), fn ($q) => $q->where('type', $filters['type']))
            ->when($filters['from'], fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn ($q, $to) => $q->whereDate('created_at', '<=', $to));

        // Totals reflect the current filter, so the cards always describe the
        // rows on screen rather than the whole table.
        $totals = (clone $query)
            ->selectRaw("COUNT(*) AS entries")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'increase' THEN quantity ELSE 0 END), 0) AS added")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'decrease' THEN quantity ELSE 0 END), 0) AS removed")
            ->first();

        return view('stock-adjustments.index', [
            'adjustments' => $query->latest()->paginate(20)->withQueryString(),
            'products' => Product::query()->orderBy('name')->get(),
            'filters' => $filters,
            'stats' => [
                'entries' => (int) $totals->entries,
                'added' => (int) $totals->added,
                'removed' => (int) $totals->removed,
                'net' => (int) $totals->added - (int) $totals->removed,
            ],
            'increaseReasons' => StockAdjustment::INCREASE_REASONS,
            'decreaseReasons' => StockAdjustment::DECREASE_REASONS,
        ]);
    }

    public function store(StockAdjustmentStoreRequest $request, StockAdjustmentService $service): RedirectResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));

        $adjustment = $service->record($product, $request->validated(), $request->user());

        return back()->with('success', sprintf(
            '%s stock %s by %d — now %d unit(s) on hand.',
            $adjustment->product_name,
            $adjustment->type === 'increase' ? 'increased' : 'decreased',
            $adjustment->quantity,
            $adjustment->stock_after,
        ));
    }
}
