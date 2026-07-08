<x-app-layout title="My Costing">
    @include('partials.page-header', [
        'title'    => 'My Costing',
        'subtitle' => 'What you have spent, grouped by requisition — click a requisition for the full breakdown',
        'actions'  => '<a href="'.route('balance.mine').'"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                           </svg>
                           Back to My Balance
                       </a>',
    ])

    {{-- Total spent --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Total Spent</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($totalSpent, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Across {{ $requisitions->total() }} requisition(s)</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4m0 0l4-4m-4 4l4 4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Grouped by requisition --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-[13px] font-bold text-[#17211c]">Spending by Requisition</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-160 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Requisition</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Purchases</th>
                        <th class="px-5 py-3">Last Purchase</th>
                        <th class="px-5 py-3 text-right">Total Spent</th>
                        <th class="px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requisitions as $row)
                        <tr class="tbl-row cursor-pointer transition hover:bg-slate-50/70"
                            onclick="window.location='{{ route('balance.spent.requisition', $row->requisition_id) }}'">
                            <td class="px-5 py-3 font-semibold text-[#287857]">
                                {{ $row->requisition_number }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ ucfirst($row->requisition_status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $row->purchase_count }}</td>
                            <td class="px-5 py-3 text-slate-500">
                                {{ \Illuminate\Support\Carbon::parse($row->last_purchase_at)->format('d M Y, h:i A') }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-rose-600">
                                − ৳ {{ number_format($row->total_spent, 2) }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('balance.spent.requisition', $row->requisition_id) }}"
                                   class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#287857] hover:underline underline-offset-2"
                                   onclick="event.stopPropagation()">
                                    Breakdown
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                You haven't spent anything yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requisitions->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $requisitions->links() }}</div>
        @endif
    </section>
</x-app-layout>
