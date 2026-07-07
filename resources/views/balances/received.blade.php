<x-app-layout title="Money Received">
    @include('partials.page-header', [
        'title'    => 'Money Received',
        'subtitle' => 'Every payment credited to your balance',
        'actions'  => '<a href="'.route('balance.mine').'"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                           </svg>
                           Back to My Balance
                       </a>',
    ])

    {{-- Total received --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="stat-card rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-600">Total Received</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($totalCredited, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $credits->total() }} payment(s)</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50">
                    <svg class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4m-9 8h10"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-[13px] font-bold text-[#17211c]">Payments Received</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Requisition</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Paid By</th>
                        <th class="px-5 py-3 text-right">Received</th>
                        <th class="px-5 py-3 text-right">Balance After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($credits as $tx)
                        @php $payment = $tx->reference; @endphp
                        <tr class="tbl-row">
                            <td class="px-5 py-3 text-slate-500">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3">
                                @if($payment?->requisition)
                                    <a class="font-semibold text-[#287857] hover:underline underline-offset-2"
                                       href="{{ route('requisitions.show', $payment->requisition) }}">
                                        {{ $payment->requisition->requisition_number }}
                                    </a>
                                @else
                                    <span class="text-slate-500">{{ $tx->note ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($payment?->payment_method)
                                    @include('partials.status', ['status' => $payment->payment_method])
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $tx->creator?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-emerald-700">
                                + ৳ {{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-800">
                                ৳ {{ number_format($tx->balance_after, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                No payments received yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($credits->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $credits->links() }}</div>
        @endif
    </section>
</x-app-layout>
