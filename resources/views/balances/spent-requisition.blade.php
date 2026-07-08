<x-app-layout title="Requisition Costing">
    @include('partials.page-header', [
        'title'    => 'Costing — '.$requisition->requisition_number,
        'subtitle' => 'Product-by-product breakdown of what you spent on this requisition',
        'actions'  => '<a href="'.route('balance.spent').'"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                           </svg>
                           Back to My Costing
                       </a>',
    ])

    {{-- Requisition total spent --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Spent on this requisition</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($requisitionTotal, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $spending->total() }} purchase(s)</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4m0 0l4-4m-4 4l4 4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-[13px] font-bold text-[#17211c]">Purchases</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Qty</th>
                        <th class="px-5 py-3">Unit Cost</th>
                        <th class="px-5 py-3">Account</th>
                        <th class="px-5 py-3 text-right">Spent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($spending as $tx)
                        @php $item = $tx->reference; @endphp
                        <tr class="tbl-row">
                            <td class="px-5 py-3 text-slate-500">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3 font-medium text-slate-800">
                                {{ $item?->product_name ?? ($tx->note ?: 'Purchase') }}
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $item?->quantity ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ $item ? '৳ '.number_format($item->purchase_price, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $item?->account?->account_name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-rose-600">
                                − ৳ {{ number_format(abs($tx->amount), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                No purchases recorded for this requisition.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($spending->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $spending->links() }}</div>
        @endif
    </section>
</x-app-layout>
