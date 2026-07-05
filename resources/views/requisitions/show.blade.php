<x-app-layout title="Requisition {{ $requisition->requisition_number }}">
    @include('partials.page-header', [
        'title'    => $requisition->requisition_number,
        'subtitle' => 'Requested by '.$requisition->employee->name.' on '.$requisition->requested_at->format('d M Y'),
    ])

    @php
        $isOwner    = auth()->id() === $requisition->employee_id;
        $isAdmin    = auth()->user()->isAdmin();
        $paidAmt    = $requisition->payments->sum('amount');
        $expenseAmt = $requisition->expenses->sum('amount');
        $balance    = $paidAmt - $expenseAmt;
    @endphp

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── Left: Items + Expenses ──────────────────────────────── --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Status + Amount summary --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/60
                        bg-white px-5 py-4 shadow-sm">
                <div>@include('partials.status', ['status' => $requisition->status])</div>
                <div class="flex flex-wrap gap-4 text-right text-sm">
                    <div class="text-slate-500">Requested
                        <div class="font-bold text-slate-800">৳ {{ number_format($requisition->total_amount, 2) }}</div>
                    </div>
                    <div class="text-slate-500">Approved
                        <div class="font-bold text-slate-800">
                            {{ $requisition->approved_amount ? '৳ '.number_format($requisition->approved_amount, 2) : '—' }}
                        </div>
                    </div>
                    <div class="text-slate-500">Paid
                        <div class="font-bold text-emerald-700">৳ {{ number_format($paidAmt, 2) }}</div>
                    </div>
                    @if($requisition->expenses->isNotEmpty())
                    <div class="text-slate-500">Expenses
                        <div class="font-bold text-rose-600">৳ {{ number_format($expenseAmt, 2) }}</div>
                    </div>
                    <div class="text-slate-500">Balance
                        <div class="font-bold {{ $balance >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            ৳ {{ number_format(abs($balance), 2) }} {{ $balance >= 0 ? 'left' : 'over' }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Product Items --}}
            @php
                $productItems = $requisition->items->filter->isProductItem();
            @endphp
            @if($productItems->isNotEmpty())
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-[13px] font-bold text-[#17211c]">Product Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-160 text-left text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Account</th>
                                <th class="px-5 py-3">Product</th>
                                <th class="px-5 py-3">Order ID</th>
                                <th class="px-5 py-3">Qty</th>
                                <th class="px-5 py-3">Unit Cost</th>
                                <th class="px-5 py-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($productItems as $item)
                            <tr class="tbl-row">
                                <td class="px-5 py-3 text-slate-700">{{ $item->account->account_name }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->product_name }}</td>
                                <td class="px-5 py-3 text-xs text-slate-400">{{ $item->order_id_daraz ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->quantity }}</td>
                                <td class="px-5 py-3 text-slate-700">৳ {{ number_format($item->purchase_price, 2) }}</td>
                                <td class="px-5 py-3 font-semibold text-slate-800">৳ {{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Other Costs (requested) --}}
            @php
                $costItems = $requisition->items->filter->isCostItem();
            @endphp
            @if($costItems->isNotEmpty())
            <div class="rounded-2xl border border-violet-100 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-violet-100 bg-violet-50/50 px-5 py-3">
                    <h2 class="text-[13px] font-bold text-violet-800">Requested Other Costs</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-violet-50/30">
                            <tr class="text-[10px] font-semibold uppercase tracking-wider text-violet-400">
                                <th class="px-5 py-3">Description</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-violet-50">
                            @foreach($costItems as $item)
                            <tr class="tbl-row">
                                <td class="px-5 py-3 text-slate-700">{{ $item->description }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-slate-800">
                                    ৳ {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ── Actual Expenses (employee logs after payment) ──── --}}
            <div class="rounded-2xl border border-amber-100 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-amber-100 bg-amber-50/40 px-5 py-3">
                    <div>
                        <h2 class="text-[13px] font-bold text-amber-800">Actual Expenses</h2>
                        <p class="text-[11px] text-amber-600/70 mt-0.5">
                            How the funds were actually spent
                        </p>
                    </div>
                    @if($requisition->expenses->isNotEmpty())
                    <div class="rounded-lg bg-amber-100 px-3 py-1.5 text-right">
                        <p class="text-[10px] font-semibold text-amber-700 uppercase tracking-wider">Total</p>
                        <p class="text-sm font-bold text-amber-800">৳ {{ number_format($expenseAmt, 2) }}</p>
                    </div>
                    @endif
                </div>

                {{-- Expense list --}}
                @if($requisition->expenses->isNotEmpty())
                <div class="divide-y divide-amber-50">
                    @foreach($requisition->expenses->sortBy('expense_date') as $expense)
                    <div class="flex items-center justify-between px-5 py-3 transition-colors hover:bg-amber-50/30">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                                <svg class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $expense->description }}</p>
                                <p class="text-[11px] text-slate-400">{{ $expense->expense_date->format('d M Y') }}</p>
                            </div>
                        </div>
                        <span class="ml-4 shrink-0 font-semibold text-amber-700">
                            ৳ {{ number_format($expense->amount, 2) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex flex-col items-center gap-2 py-8 text-center">
                    <svg class="h-8 w-8 text-amber-200" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm text-slate-400">No expenses recorded yet.</p>
                </div>
                @endif

                {{-- Add expense form — employee only, approved requisition --}}
                @if($isOwner && $requisition->status === 'approved' && $paidAmt > 0)
                <div class="border-t border-amber-100 bg-amber-50/30 px-5 py-4">
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-amber-700">
                        + Add Expense
                    </p>
                    <form method="post"
                          action="{{ route('requisitions.expenses.store', $requisition) }}"
                          class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="flex-1 min-w-44">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                Description <span class="text-red-400">*</span>
                            </label>
                            <input name="description" required
                                   placeholder="e.g. Car rental, food, tools…"
                                   class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm
                                          text-slate-700 placeholder-slate-300
                                          focus:border-amber-400 focus:outline-none">
                        </div>
                        <div class="w-36">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                Amount (৳) <span class="text-red-400">*</span>
                            </label>
                            <input name="amount" type="number" min="0.01" step="0.01" required
                                   placeholder="0.00"
                                   class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm
                                          text-slate-700 placeholder-slate-300
                                          focus:border-amber-400 focus:outline-none">
                        </div>
                        <div class="w-40">
                            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                Date <span class="text-red-400">*</span>
                            </label>
                            <input name="expense_date" type="date"
                                   value="{{ now()->toDateString() }}" required
                                   class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm
                                          text-slate-700 focus:border-amber-400 focus:outline-none">
                        </div>
                        <button class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white
                                       shadow-sm transition hover:bg-amber-600 flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add
                        </button>
                    </form>
                </div>
                @elseif($isOwner && $requisition->status !== 'approved')
                <div class="border-t border-amber-100 px-5 py-3">
                    <p class="text-[11px] text-slate-400">
                        Expenses can be logged once the requisition is approved and payment is made.
                    </p>
                </div>
                @endif
            </div>

            {{-- Admin note --}}
            @if($requisition->admin_note)
            <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <span class="font-semibold">Admin note:</span> {{ $requisition->admin_note }}
            </div>
            @endif

        </div>

        {{-- ── Right sidebar ───────────────────────────────────────── --}}
        <aside class="space-y-5">

            {{-- Review form (admin, pending/hold) --}}
            @if($isAdmin && in_array($requisition->status, ['pending', 'hold'], true))
            <form method="post" action="{{ route('requisitions.review', $requisition) }}"
                  class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-3">
                @csrf
                <h2 class="text-[13px] font-bold text-[#17211c]">Review Requisition</h2>
                <select name="status"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                               text-slate-700 focus:border-[#287857] focus:outline-none" required>
                    <option value="approved">Approve</option>
                    <option value="hold">Hold</option>
                    <option value="rejected">Reject</option>
                </select>
                <input name="approved_amount" type="number" step="0.01"
                       value="{{ $requisition->total_amount }}"
                       placeholder="Approved amount"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                              text-slate-700 focus:border-[#287857] focus:outline-none">
                <textarea name="admin_note" rows="3" placeholder="Note or reject reason"
                          class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                 text-slate-700 focus:border-[#287857] focus:outline-none"></textarea>
                <button class="w-full rounded-xl bg-[#287857] px-4 py-2.5 text-sm font-semibold text-white
                               transition hover:bg-[#1f6046]">
                    Submit Review
                </button>
            </form>
            @endif

            {{-- Payment form (admin, approved) --}}
            @if($isAdmin && $requisition->status === 'approved')
            <form method="post" action="{{ route('requisitions.payments.store', $requisition) }}"
                  class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm space-y-3">
                @csrf
                <h2 class="text-[13px] font-bold text-[#17211c]">Record Payment</h2>
                <input name="amount" type="number" step="0.01" placeholder="Amount" required
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                              text-slate-700 focus:border-[#287857] focus:outline-none">
                <select name="payment_method" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                               text-slate-700 focus:border-[#287857] focus:outline-none">
                    @foreach(['cash','bkash','nagad','bank'] as $method)
                        <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                    @endforeach
                </select>
                <input name="payment_date" type="datetime-local"
                       value="{{ now()->format('Y-m-d\TH:i') }}" required
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                              text-slate-700 focus:border-[#287857] focus:outline-none">
                <input name="reference" placeholder="Reference"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                              text-slate-700 focus:border-[#287857] focus:outline-none">
                <textarea name="note" rows="2" placeholder="Payment note"
                          class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                 text-slate-700 focus:border-[#287857] focus:outline-none"></textarea>
                <button class="w-full rounded-xl bg-[#17211c] px-4 py-2.5 text-sm font-semibold text-white
                               transition hover:bg-black">
                    Save Payment
                </button>
            </form>
            @endif

            {{-- Payments list --}}
            <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-[13px] font-bold text-[#17211c]">Payments</h2>
                <div class="divide-y divide-slate-100">
                    @php
                        $methodColors = [
                            'bkash'  => 'bg-pink-50 text-pink-700',
                            'nagad'  => 'bg-orange-50 text-orange-700',
                            'bank'   => 'bg-indigo-50 text-indigo-700',
                            'cash'   => 'bg-emerald-50 text-emerald-700',
                        ];
                    @endphp
                    @forelse($requisition->payments as $payment)
                    @php $mc = $methodColors[$payment->payment_method] ?? 'bg-slate-100 text-slate-600'; @endphp
                    <div class="py-3">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-slate-800">
                                ৳ {{ number_format($payment->amount, 2) }}
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $mc }}">
                                {{ ucfirst($payment->payment_method) }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-400">
                            {{ $payment->payment_date->format('d M Y, h:i A') }}
                        </p>
                    </div>
                    @empty
                    <p class="py-4 text-sm text-slate-400">No payments recorded.</p>
                    @endforelse
                </div>
            </section>

        </aside>
    </div>
</x-app-layout>
