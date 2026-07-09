<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total'     => Product::query()->count(),
            'units'     => (int) Product::query()->sum('current_stock'),
            'low_stock' => Product::query()->where('current_stock', '<=', 5)->count(),
            'inv_value' => (float) Product::query()
                ->selectRaw('COALESCE(SUM(COALESCE(default_purchase_price, 0) * current_stock), 0) AS agg')
                ->value('agg'),
        ];

        return view('products.index', [
            'products' => Product::query()->orderBy('name')->paginate(20),
            'stats' => $stats,
        ]);
    }

    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return back()->with('success', 'Product saved.');
    }

    public function update(ProductStoreRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            // Replace any existing image to avoid orphaned files.
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image') && $product->image) {
            Storage::disk('public')->delete($product->image);
            $data['image'] = null;
        }

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }
}
