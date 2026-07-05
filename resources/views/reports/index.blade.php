<x-app-layout title="Reports">
    @include('partials.page-header', [
        'title'    => 'Reports',
        'subtitle' => 'Revenue, cost, profit, stock, and return reporting',
        'actions'  => '<a href="'.route('reports.export', request()->query()).'"
                          class="inline-flex items-center gap-1.5 rounded-xl bg-[#17211c] px-4 py-2.5
                                 text-[12px] font-semibold text-white shadow-sm transition hover:bg-black">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round"
                                     d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                           </svg>
                           Export CSV
                       </a>',
    ])

    {{-- Date filter --}}
    <form method="get"
          class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                          focus:border-[#287857] focus:outline-none">
        </div>
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                          focus:border-[#287857] focus:outline-none">
        </div>
        <button class="rounded-xl bg-[#287857] px-5 py-2.5 text-[12px] font-semibold text-white
                       transition hover:bg-[#1f6046]">
            Apply Filter
        </button>
    </form>

    {{-- Summary cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Revenue',          $summary['revenue'],  'emerald', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
            ['Paid Cost',        $summary['cost'],     'sky',     'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['Pending Requests', $summary['pending'],  'amber',   'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Net Profit',       $summary['profit'],   'violet',  'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ] as [$label, $value, $color, $path])
            <div class="stat-card rounded-2xl border border-{{ $color }}-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-{{ $color }}-600">
                            {{ $label }}
                        </p>
                        <p class="mt-2 text-xl font-bold text-[#17211c]">৳ {{ number_format($value, 2) }}</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-{{ $color }}-50">
                        <svg class="h-5 w-5 text-{{ $color }}-500" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Account Revenue + Stock --}}
    <div class="mb-6 grid gap-5 xl:grid-cols-2">

        {{-- Account Revenue --}}
        <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-[13px] font-bold text-[#17211c]">Account Revenue</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Sales revenue by Daraz account</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">Shop</th>
                            <th class="px-5 py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($accountRows as $row)
                            <tr class="tbl-row">
                                <td class="px-5 py-3 font-medium text-slate-800">{{ $row->account_name }}</td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ $row->shop_name }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-emerald-700">
                                    ৳ {{ number_format($row->revenue, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Current Stock --}}
        <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-[13px] font-bold text-[#17211c]">Current Stock</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Live inventory balance per product</p>
            </div>
            <div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                @foreach($stockRows as $row)
                    <div class="flex items-center justify-between px-5 py-3 transition-colors hover:bg-slate-50/70">
                        <span class="text-sm text-slate-700">{{ $row->name }}</span>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold
                                     {{ $row->current_stock <= 5 ? 'bg-rose-50 text-rose-700' : ($row->current_stock <= 20 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">
                            {{ $row->current_stock }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Returns table --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-[13px] font-bold text-[#17211c]">Returns in Period</h2>
            <p class="text-[11px] text-slate-400 mt-0.5">Product returns within selected date range</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-160 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">Qty</th>
                        <th class="px-5 py-3">Condition</th>
                        <th class="px-5 py-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($returnRows as $row)
                        <tr class="tbl-row">
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $row->return_date->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $row->product_name }}</td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $row->quantity }}</td>
                            <td class="px-5 py-3">
                                @if($row->condition === 'good')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50
                                                 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Good
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50
                                                 px-2.5 py-1 text-[10px] font-semibold text-rose-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>Damaged
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ $row->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">
                                No returns in this date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
