<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentStoreRequest;
use App\Models\Payment;
use App\Models\Requisition;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with('requisition.employee')
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('paid_to', $request->user()->id))
            ->latest('payment_date')
            ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function store(PaymentStoreRequest $request, Requisition $requisition, PaymentService $service): RedirectResponse
    {
        $service->create($requisition, $request->validated(), $request->user());

        return back()->with('success', 'Payment recorded.');
    }
}
