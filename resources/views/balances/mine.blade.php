<x-app-layout title="My Balance">
    @include('partials.page-header', [
        'title'    => 'My Balance',
        'subtitle' => 'Money paid to you, what you have spent, and your current balance',
    ])

    {{-- ── Summary cards (each links to its own breakdown page) ─────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

        {{-- Total received --}}
        <a href="{{ route('balance.received') }}"
           class="stat-card group rounded-2xl border border-sky-100 bg-white p-5 shadow-sm
                  transition hover:border-sky-300 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-600">Total Received</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($totalCredited, 2) }}</p>
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-sky-500">
                        View breakdown
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50">
                    <svg class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4m-9 8h10"/>
                    </svg>
                </div>
            </div>
        </a>

        {{-- Total spent --}}
        <a href="{{ route('balance.spent') }}"
           class="stat-card group rounded-2xl border border-rose-100 bg-white p-5 shadow-sm
                  transition hover:border-rose-300 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Total Spent</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($totalSpent, 2) }}</p>
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-rose-500">
                        View breakdown
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4m0 0l4-4m-4 4l4 4"/>
                    </svg>
                </div>
            </div>
        </a>

        {{-- Current balance --}}
        <a href="{{ route('balance.statement') }}"
           class="stat-card group rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm
                  transition hover:border-emerald-300 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Current Balance</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($user->balance, 2) }}</p>
                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-500">
                        View full statement
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    {{-- ── Recent activity ─────────────────────────────────────────── --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-[13px] font-bold text-[#17211c]">Recent Activity</h2>
            <a href="{{ route('balance.statement') }}"
               class="text-[11px] font-semibold text-[#287857] hover:underline underline-offset-2">
                View full statement →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-160 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Balance After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recent as $tx)
                        @php $isDebit = $tx->amount < 0; @endphp
                        <tr class="tbl-row">
                            <td class="px-5 py-3 text-slate-500">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $tx->note ?: ucfirst(str_replace('_', ' ', $tx->type)) }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $isDebit ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ $isDebit ? '−' : '+' }} ৳ {{ number_format(abs($tx->amount), 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-800">
                                ৳ {{ number_format($tx->balance_after, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">
                                No balance activity yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
