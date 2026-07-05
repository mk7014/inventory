<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnStoreRequest;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(): View
    {
        return view('returns.index', [
            'returns' => ProductReturn::query()->with('sale')->latest('return_date')->paginate(15),
            'sales' => Sale::query()->where('status', 'completed')->latest('sold_date')->limit(100)->get(),
        ]);
    }

    public function store(ReturnStoreRequest $request, ReturnService $service): RedirectResponse
    {
        $sale = Sale::findOrFail($request->validated('sale_id'));
        $service->create($sale, $request->validated(), $request->user());

        return back()->with('success', 'Return recorded.');
    }
}
