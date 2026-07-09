<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Models\Warehouse;
use App\Services\DeletionService;
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

    public function destroy(Warehouse $warehouse, DeletionService $service): RedirectResponse
    {
        $service->deleteWarehouse($warehouse);

        return back()->with('success', 'Warehouse deleted.');
    }
}
