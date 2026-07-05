<x-app-layout title="Expenses">
    @include('partials.page-header', [
        'title'    => 'Expenses',
        'subtitle' => 'Actual spending records by employees',
    ])

    {{-- Filter --}}
    <form method="get"
          class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
            <input type="date" name="from" value="{{ $from ?? '' }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                          focus:border-amber-400 focus:outline-none">
        </div>
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">To</label>
            <input type="date" name="to" value="{{ $to ?? '' }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                          focus:border-amber-400 focus:outline-none">
        </div>
        <button class="rounded-xl bg-amber-500 px-5 py-2.5 text-[12px] font-semibold text-white transition hover:bg-amber-600">
            Filter
        </button>
        @if(request()->hasAny(['from','to']))
        <a href="{{ route('expenses.index') }}"
           class="rounded-xl border border-slate-200 px-4 py-2.5 text-[12px] font-semibold text-slate-500
                  transition hover:bg-slate-50">
            Clear
        </a>
        @endif
    </form>

    {{-- Table --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">All Expenses</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $expenses->total() }} records</p>
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-2 text-right">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-600">Total</p>
                <p class="text-sm font-bold text-amber-700">৳ {{ number_format($total, 2) }}</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Requisition</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $expense)
                    <tr class="tbl-row">
                        <td class="px-5 py-3 text-xs text-slate-400">
                            {{ $expense->expense_date->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full
                                            bg-linear-to-br from-amber-400 to-amber-600
                                            text-[9px] font-bold text-white">
                                    {{ strtoupper(substr($expense->creator->name ?? '?', 0, 2)) }}
                                </div>
                                <span class="text-slate-700">{{ $expense->creator->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ route('requisitions.show', $expense->requisition_id) }}"
                               class="font-medium text-[#287857] hover:underline">
                                {{ $expense->requisition->requisition_number ?? '—' }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-slate-700">{{ $expense->description }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-amber-700">
                            ৳ {{ number_format($expense->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <svg class="h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-sm">No expenses recorded yet.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $expenses->links() }}</div>
        @endif
    </section>
</x-app-layout>
