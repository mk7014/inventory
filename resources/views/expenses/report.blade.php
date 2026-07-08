@php
    $palette = ['#059669','#0ea5e9','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#64748b'];
    $exportQuery = array_filter($filters ?? []);
@endphp
<x-app-layout title="Expense Report">
    @include('partials.page-header', [
        'title'    => 'Expense Report & Breakdown',
        'subtitle' => 'Where your money goes — by category and over time',
        'actions'  => '<div class="flex items-center gap-2">
            <a href="'.route('expenses.index').'"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
               <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
               Back
            </a>
            <a href="'.route('expenses.export', $exportQuery).'"
               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-[12px] font-semibold text-white shadow-sm transition hover:bg-emerald-700">
               <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
               Export CSV
            </a>
        </div>',
    ])

    {{-- Filters --}}
    <form method="get" class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
        @if($isAdmin)
        <div class="min-w-40">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">User</label>
            <select name="user_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 focus:border-emerald-400 focus:outline-none">
                <option value="">All users</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(request('user_id') == $emp->id)>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="min-w-32">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 focus:border-emerald-400 focus:outline-none">
        </div>
        <div class="min-w-32">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 focus:border-emerald-400 focus:outline-none">
        </div>
        <button class="rounded-xl bg-slate-800 px-5 py-2.5 text-[12px] font-semibold text-white transition hover:bg-slate-900">Filter</button>
        @if(request()->hasAny(['from','to','user_id']))
            <a href="{{ route('expenses.report') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-[12px] font-semibold text-slate-500 transition hover:bg-slate-50">Clear</a>
        @endif
    </form>

    {{-- Totals --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Total Spent</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($total, 2) }}</p>
        </div>
        <div class="stat-card rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Records</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">{{ number_format($count) }}</p>
        </div>
        <div class="stat-card rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Average / Record</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($count ? $total / $count : 0, 2) }}</p>
        </div>
    </div>

    @if($count === 0)
        <div class="rounded-2xl border border-slate-200/60 bg-white px-5 py-16 text-center text-sm text-slate-400 shadow-sm">
            No expenses match these filters.
        </div>
    @else
    <div class="grid gap-6 xl:grid-cols-5">
        {{-- Category breakdown --}}
        <section class="xl:col-span-3 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-[13px] font-bold text-[#17211c]">By Category</h2></div>
            <div class="grid gap-4 p-5 sm:grid-cols-2">
                <div class="flex items-center justify-center">
                    <div class="w-full max-w-56"><canvas id="catChart"></canvas></div>
                </div>
                <div class="space-y-2">
                    @foreach($byCategory as $i => $row)
                        @php $pct = $total > 0 ? ($row->total / $total) * 100 : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-[12px]">
                                <span class="flex items-center gap-2 font-medium text-slate-700">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $palette[$i % count($palette)] }}"></span>
                                    {{ $row->category }}
                                </span>
                                <span class="font-semibold text-slate-800">৳ {{ number_format($row->total, 2) }}</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $palette[$i % count($palette)] }}"></div>
                            </div>
                            <p class="mt-0.5 text-[10px] text-slate-400">{{ $row->count }} record(s) · {{ number_format($pct, 1) }}%</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Monthly trend --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-[13px] font-bold text-[#17211c]">Monthly Trend</h2></div>
            <div class="p-5"><canvas id="monthChart" height="220"></canvas></div>
        </section>
    </div>
    @endif

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') return;
        const palette = @json($palette);

        const catEl = document.getElementById('catChart');
        if (catEl) {
            new Chart(catEl, {
                type: 'doughnut',
                data: {
                    labels: @json($byCategory->pluck('category')),
                    datasets: [{ data: @json($byCategory->pluck('total')->map(fn($v) => (float) $v)), backgroundColor: palette, borderWidth: 0 }],
                },
                options: { cutout: '62%', plugins: { legend: { display: false } } },
            });
        }

        const monEl = document.getElementById('monthChart');
        if (monEl) {
            new Chart(monEl, {
                type: 'bar',
                data: {
                    labels: @json($byMonth->pluck('ym')),
                    datasets: [{ label: 'Spent (৳)', data: @json($byMonth->pluck('total')->map(fn($v) => (float) $v)), backgroundColor: '#f43f5e', borderRadius: 6 }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        }
    });
    </script>
    @endpush
</x-app-layout>
