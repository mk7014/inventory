<?php

namespace App\Http\Controllers;

use App\Models\DirectPurchase;
use App\Services\DirectPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DirectPurchaseApprovalController extends Controller
{
    public function __invoke(Request $request, DirectPurchase $purchase, DirectPurchaseService $service): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'cancel'])],
        ]);

        if ($data['action'] === 'approve') {
            $service->approve($purchase, $request->user());

            return back()->with('success', 'Direct purchase approved — stock received'
                .($purchase->isAdvance() ? ' and balance deducted.' : '. Outstanding due recorded.'));
        }

        $service->cancel($purchase, $request->user());

        return back()->with('success', 'Direct purchase cancelled.');
    }
}
