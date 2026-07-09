<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectPurchasePaymentStoreRequest;
use App\Models\DirectPurchase;
use App\Models\DirectPurchasePayment;
use App\Services\DirectPurchasePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectPurchasePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = DirectPurchasePayment::query()
            ->with(['directPurchase.supplier', 'paidTo', 'paidBy'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('paid_to', $request->user()->id))
            ->latest('payment_date')
            ->paginate(20);

        return view('direct-purchase-payments.index', compact('payments'));
    }

    public function store(DirectPurchasePaymentStoreRequest $request, DirectPurchase $purchase, DirectPurchasePaymentService $service): RedirectResponse
    {
        $service->create($purchase, $request->validated(), $request->user());

        return back()->with('success', 'Payment recorded against the direct purchase.');
    }
}
