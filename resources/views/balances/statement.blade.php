<x-app-layout title="Balance Statement">
    @include('partials.page-header', [
        'title'    => 'Balance Statement',
        'subtitle' => 'How your current balance was built up — every credit and debit',
        'actions'  => '<a href="'.route('balance.mine').'"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                           </svg>
                           Back to My Balance
                       </a>',
    ])

    {{-- Received − Spent = Balance --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-600">Total Received</p>
            <p class="mt-2 text-xl font-bold text-[#17211c]">৳ {{ number_format($totalCredited, 2) }}</p>
        </div>
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Total Spent</p>
            <p class="mt-2 text-xl font-bold text-[#17211c]">− ৳ {{ number_format($totalSpent, 2) }}</p>
        </div>
        <div class="stat-card rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Current Balance</p>
            <p class="mt-2 text-xl font-bold {{ (float) $user->balance < 0 ? 'text-red-600' : 'text-[#17211c]' }}">৳ {{ number_format($user->balance, 2) }}</p>
        </div>
    </div>

    {{-- Full running ledger --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Full Statement</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $transactions->total() }} transactions</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3">By</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Balance After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        @php $isDebit = $tx->amount < 0; @endphp
                        <tr class="tbl-row">
                            <td class="px-5 py-3 text-slate-500">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold
                                             {{ $isDebit ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $isDebit ? 'Spent' : 'Received' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $tx->note ?: ucfirst(str_replace('_', ' ', $tx->type)) }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $tx->creator?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $isDebit ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ $isDebit ? '−' : '+' }} ৳ {{ number_format(abs($tx->amount), 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-800">
                                ৳ {{ number_format($tx->balance_after, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">
                                No balance activity yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $transactions->links() }}</div>
        @endif
    </section>
</x-app-layout>
