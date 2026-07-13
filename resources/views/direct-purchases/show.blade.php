<x-app-layout title="Direct Purchase {{ $purchase->purchase_number }}">
    @include('partials.page-header', [
        'title'    => $purchase->purchase_number,
        'subtitle' => 'For '.$purchase->employee->name.' • '.$purchase->purchase_date->format('d M Y'),
    ])

    @php
        $isAdmin  = auth()->user()->isAdmin();
        $canApprove = $isAdmin || auth()->user()->hasPermission('direct_purchases.approve');
        $due      = $purchase->dueAmount();
    @endphp

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── Left: Items + Details ───────────────────────────────── --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Summary bar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
                <div class="flex items-center gap-2">
                    @include('partials.status', ['status' => $purchase->status])
                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $purchase->payment_type === 'advance' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ ucfirst($purchase->payment_type) }}
                    </span>
                    @if($purchase->status === 'approved')
                        @include('partials.status', ['status' => $purchase->payment_status])
                    @endif
                </div>
                <div class="flex flex-wrap gap-4 text-right text-sm">
                    <div class="text-slate-500">Grand Total
                        <div class="font-bold text-slate-800">৳ {{ number_format($purchase->grand_total, 2) }}</div>
                    </div>
                    @if($purchase->isDue())
                    <div class="text-slate-500">Paid
                        <div class="font-bold text-emerald-700">৳ {{ number_format($purchase->paid_amount, 2) }}</div>
                    </div>
                    <div class="text-slate-500">Due
                        <div class="font-bold {{ $due > 0 ? 'text-rose-600' : 'text-emerald-700' }}">৳ {{ number_format($due, 2) }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Items --}}
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-[13px] font-bold text-[#17211c]">Products</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-200 text-left text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Product</th>
                                <th class="px-5 py-3">SKU</th>
                                <th class="px-5 py-3">Qty</th>
                                <th class="px-5 py-3">Unit Price</th>
                                <th class="px-5 py-3">Discount</th>
                                <th class="px-5 py-3">Tax</th>
                                <th class="px-5 py-3 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($purchase->items as $item)
                            <tr class="tbl-row">
                                <td class="px-5 py-3 text-slate-700">{{ $item->product_name }}</td>
                                <td class="px-5 py-3 text-xs text-slate-400">{{ $item->sku ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="px-5 py-3 text-slate-700">৳ {{ number_format($item->purchase_price, 2) }}</td>
                                <td class="px-5 py-3 text-slate-600">৳ {{ number_format($item->discount, 2) }}</td>
                                <td class="px-5 py-3 text-slate-600">৳ {{ number_format($item->tax, 2) }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-slate-800">৳ {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50/50 text-sm">
                            <tr>
                                <td colspan="6" class="px-5 py-2 text-right text-slate-500">Subtotal</td>
                                <td class="px-5 py-2 text-right text-slate-700">৳ {{ number_format($purchase->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-5 py-2 text-right text-slate-500">Discount</td>
                                <td class="px-5 py-2 text-right text-slate-700">− ৳ {{ number_format($purchase->discount_total, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-5 py-2 text-right text-slate-500">Tax</td>
                                <td class="px-5 py-2 text-right text-slate-700">+ ৳ {{ number_format($purchase->tax_total, 2) }}</td>
                            </tr>
                            <tr class="font-bold text-[#17211c]">
                                <td colspan="6" class="px-5 py-2.5 text-right">Grand Total</td>
                                <td class="px-5 py-2.5 text-right text-[#287857]">৳ {{ number_format($purchase->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Details card --}}
            <div class="grid gap-4 rounded-2xl border border-slate-200/60 bg-white p-5 text-sm shadow-sm sm:grid-cols-2">
                <div><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Supplier</p><p class="mt-0.5 text-slate-700">{{ $purchase->supplier?->name ?? '—' }}</p></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Warehouse</p><p class="mt-0.5 text-slate-700">{{ $purchase->warehouse?->name ?? '—' }}</p></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Invoice No</p><p class="mt-0.5 text-slate-700">{{ $purchase->invoice_number ?: '—' }}</p></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference No</p><p class="mt-0.5 text-slate-700">{{ $purchase->reference_number ?: '—' }}</p></div>
                @if($purchase->remarks)
                <div class="sm:col-span-2"><p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Remarks</p><p class="mt-0.5 text-slate-700">{{ $purchase->remarks }}</p></div>
                @endif
            </div>
        </div>

        {{-- ── Right sidebar ───────────────────────────────────────── --}}
        <aside class="space-y-5">

            {{-- Approve / Cancel (pending) --}}
            @if($canApprove && $purchase->status === 'pending')
            <div class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-3">
                <h2 class="text-[13px] font-bold text-[#17211c]">Review Purchase</h2>
                <p class="text-[11px] text-slate-400">
                    Approving receives {{ $purchase->items->sum('quantity') }} unit(s) into stock.
                    @if($purchase->isAdvance())
                        ৳{{ number_format($purchase->grand_total, 2) }} will be deducted from {{ $purchase->employee->name }}'s balance.
                    @else
                        ৳{{ number_format($purchase->grand_total, 2) }} will be deducted from {{ $purchase->employee->name }}'s balance — it may go negative, which is the company's debt. Recording a payment credits it back.
                    @endif
                </p>
                <form method="post" action="{{ route('direct-purchases.review', $purchase) }}"
                      onsubmit="return confirm('Approve this direct purchase? Stock will be received.');">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button class="w-full rounded-xl bg-[#287857] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1f6046]">
                        Approve &amp; Receive Stock
                    </button>
                </form>
                <form method="post" action="{{ route('direct-purchases.review', $purchase) }}"
                      onsubmit="return confirm('Cancel this direct purchase?');">
                    @csrf
                    <input type="hidden" name="action" value="cancel">
                    <button class="w-full rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                        Cancel Purchase
                    </button>
                </form>
            </div>
            @endif

            {{-- Record payment (due, approved, outstanding) --}}
            @if($canApprove && $purchase->isDue() && $purchase->status === 'approved' && $due > 0)
            <form method="post" action="{{ route('direct-purchases.payments.store', $purchase) }}"
                  class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-3">
                @csrf
                <h2 class="text-[13px] font-bold text-[#17211c]">Record Payment</h2>
                <p class="text-[11px] text-slate-400">Outstanding due: <span class="font-semibold text-rose-600">৳ {{ number_format($due, 2) }}</span></p>
                <p class="text-[11px] text-slate-400">The paid amount is credited back to {{ $purchase->employee->name }}'s balance.</p>
                <input name="amount" type="number" step="0.01" min="0.01" max="{{ $due }}" placeholder="Amount" required
                       class="ppp-field">
                <select name="payment_method" required
                        class="ppp-field">
                    @foreach(['cash','bkash','nagad','bank'] as $method)
                        <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                    @endforeach
                </select>
                <input name="payment_date" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" required
                       class="ppp-field">
                <input name="reference" placeholder="Reference"
                       class="ppp-field">
                <textarea name="note" rows="2" placeholder="Payment note"
                          class="ppp-field"></textarea>
                <button class="w-full rounded-xl bg-[#17211c] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-black">Save Payment</button>
            </form>
            @endif

            {{-- Payment history --}}
            @if($purchase->isDue())
            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-[13px] font-bold text-[#17211c]">Payment History</h2>
                <div class="divide-y divide-slate-100">
                    @php
                        $methodColors = [
                            'bkash' => 'bg-pink-50 text-pink-700', 'nagad' => 'bg-orange-50 text-orange-700',
                            'bank' => 'bg-indigo-50 text-indigo-700', 'cash' => 'bg-emerald-50 text-emerald-700',
                        ];
                    @endphp
                    @forelse($purchase->payments->sortByDesc('payment_date') as $payment)
                    @php $mc = $methodColors[$payment->payment_method] ?? 'bg-slate-100 text-slate-600'; @endphp
                    <div class="py-3">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-slate-800">৳ {{ number_format($payment->amount, 2) }}</span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $mc }}">{{ ucfirst($payment->payment_method) }}</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $payment->payment_date->format('d M Y, h:i A') }}
                            @if($payment->reference)• {{ $payment->reference }}@endif</p>
                    </div>
                    @empty
                    <p class="py-4 text-sm text-slate-400">No payments recorded.</p>
                    @endforelse
                </div>
            </section>
            @endif

            {{-- Meta --}}
            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 text-sm shadow-sm space-y-2">
                <div class="flex justify-between"><span class="text-slate-400">Created by</span><span class="text-slate-700">{{ $purchase->creator?->name ?? '—' }}</span></div>
                @if($purchase->approver)
                <div class="flex justify-between"><span class="text-slate-400">Approved by</span><span class="text-slate-700">{{ $purchase->approver->name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Approved at</span><span class="text-slate-700">{{ $purchase->approved_at->format('d M Y, h:i A') }}</span></div>
                @endif
            </section>
        </aside>
    </div>
</x-app-layout>
