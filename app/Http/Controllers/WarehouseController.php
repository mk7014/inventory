<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        return view('warehouses.index', ['warehouses' => Warehouse::query()->latest()->paginate(20)]);
    }

    public function store(WarehouseStoreRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return back()->with('success', 'Warehouse saved.');
    }

    public function update(WarehouseStoreRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return back()->with('success', 'Warehouse updated.');
    }
}
