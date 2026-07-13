<x-app-layout title="Direct Purchase {{ $purchase->purchase_number }}">
    @include('partials.page-header', [
        'title'    => $purchase->purchase_number,
        'subtitle' => 'For '.$purchase->employee->name.' • '.$purchase->purchase_date->format('d M Y'),
    ])

    @php
        $isAdmin  = auth()->user()->isAdmin();
        $canApprove = $isAdmin || auth()->user()->hasPermission('direct_purchases.approve');
    @endphp

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── Left: Items + Details ───────────────────────────────── --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Summary bar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
                <div class="flex items-center gap-2">
                    @include('partials.status', ['status' => $purchase->status])
                </div>
                <div class="flex flex-wrap gap-4 text-right text-sm">
                    <div class="text-slate-500">Grand Total
                        <div class="font-bold text-slate-800">৳ {{ number_format($purchase->grand_total, 2) }}</div>
                    </div>
                    @if($purchase->status === 'approved')
                    <div class="text-slate-500">Deducted from balance
                        <div class="font-bold text-rose-600">− ৳ {{ number_format($purchase->grand_total, 2) }}</div>
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
                    Approving receives {{ $purchase->items->sum('quantity') }} unit(s) into stock and deducts
                    ৳{{ number_format($purchase->grand_total, 2) }} from {{ $purchase->employee->name }}'s balance
                    (currently ৳{{ number_format((float) $purchase->employee->balance, 2) }}). The balance may go negative —
                    that is what the company owes back, and it clears when their balance is credited again.
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

            {{-- Balance effect (approved) --}}
            @if($purchase->status === 'approved')
            <section class="rounded-2xl border border-amber-100 bg-amber-50/50 p-5 shadow-sm">
                <h2 class="text-[13px] font-bold text-[#17211c]">Balance Effect</h2>
                <p class="mt-1 text-[11px] text-slate-500">
                    ৳{{ number_format($purchase->grand_total, 2) }} was deducted from {{ $purchase->employee->name }}'s balance
                    on approval, which now stands at
                    <span class="font-semibold {{ (float) $purchase->employee->balance < 0 ? 'text-red-600' : 'text-slate-700' }}">৳{{ number_format((float) $purchase->employee->balance, 2) }}</span>.
                    A negative balance is what the company owes back; it clears automatically the next time their balance is credited.
                </p>
                @if($purchase->employee_id === auth()->id())
                    <a href="{{ route('balance.statement') }}" class="mt-2 inline-block text-[11px] font-semibold text-[#287857] hover:underline">View my balance statement</a>
                @elseif($isAdmin)
                    <a href="{{ route('balances.index') }}" class="mt-2 inline-block text-[11px] font-semibold text-[#287857] hover:underline">View employee balances</a>
                @endif
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
