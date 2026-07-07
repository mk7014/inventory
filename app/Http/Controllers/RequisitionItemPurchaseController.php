<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RequisitionItemPurchaseController extends Controller
{
    public function __invoke(Request $request, Requisition $requisition, RequisitionItem $item, PurchaseService $service): RedirectResponse
    {
        abort_unless($requisition->employee_id === $request->user()->id, 403);
        abort_unless($item->requisition_id === $requisition->id, 404);

        $service->purchaseItem($item, $request->user());

        return back()->with('success', 'Purchase recorded — stock updated and balance deducted.');
    }
}
