<x-app-layout title="Dashboard">
    @include('partials.page-header', ['title' => 'Dashboard', 'subtitle' => 'Current month operational summary'])

    {{-- ── Stat cards ──────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">

        {{-- Pending --}}
        <div class="stat-card rounded-2xl border border-amber-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-amber-600">Pending</p>
                    <p class="mt-2 text-xl font-bold text-[#17211c]">৳ {{ number_format($summary['pending_amount'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $summary['pending_count'] }} requisitions</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50">
                    <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Paid --}}
        <div class="stat-card rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-sky-600">Paid</p>
                    <p class="mt-2 text-xl font-bold text-[#17211c]">৳ {{ number_format($summary['paid_amount'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">This month</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50">
                    <svg class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="stat-card rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Revenue</p>
                    <p class="mt-2 text-xl font-bold text-[#17211c]">৳ {{ number_format($summary['sales_revenue'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Completed sales</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Net Profit --}}
        <div class="stat-card rounded-2xl border border-violet-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-violet-600">Net Profit</p>
                    <p class="mt-2 text-xl font-bold text-[#17211c]">৳ {{ number_format($summary['net_profit'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Revenue minus paid</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50">
                    <svg class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Stock --}}
        <div class="stat-card rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-indigo-600">Stock</p>
                    <p class="mt-2 text-xl font-bold text-[#17211c]">{{ number_format($summary['stock_count']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Available units</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Returns --}}
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Returns</p>
                    <p class="mt-2 text-xl font-bold text-[#17211c]">{{ number_format($summary['returns_count']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">This month</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Middle row: Requisitions table + Doughnut chart ─────────── --}}
    <div class="mt-5 grid gap-5 xl:grid-cols-3">

        {{-- Recent Requisitions --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Recent Requisitions</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Latest submitted requests</p>
                </div>
                <a href="{{ route('requisitions.index') }}"
                   class="rounded-lg border border-[#287857]/30 bg-emerald-50 px-3 py-1.5
                          text-[11px] font-semibold text-[#287857] transition-all duration-200
                          hover:bg-[#287857] hover:text-white hover:border-transparent">
                    View all
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50/70">
                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">No</th>
                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Employee</th>
                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-5 py-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentRequisitions as $row)
                            <tr class="tbl-row">
                                <td class="px-5 py-3">
                                    <a class="font-semibold text-[#287857] hover:underline underline-offset-2"
                                       href="{{ route('requisitions.show', $row) }}">{{ $row->requisition_number }}</a>
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $row->employee->name }}</td>
                                <td class="px-5 py-3 font-medium text-slate-800">৳ {{ number_format($row->total_amount, 2) }}</td>
                                <td class="px-5 py-3">@include('partials.status', ['status' => $row->status])</td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ $row->requested_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">
                                    No requisitions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Status Chart --}}
        <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-[13px] font-bold text-[#17211c]">Status Distribution</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Requisition breakdown</p>
            </div>
            <div class="flex items-center justify-center p-5">
                <canvas id="statusChart" class="max-h-56 w-full"></canvas>
            </div>
        </section>

    </div>

    {{-- ── Bottom row: Accounts + Low Stock ───────────────────────── --}}
    <div class="mt-5 grid gap-5 lg:grid-cols-2">

        {{-- Daraz Accounts --}}
        <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-[13px] font-bold text-[#17211c]">Daraz Accounts</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Connected store accounts</p>
            </div>
            <div class="p-4 grid gap-3 sm:grid-cols-2">
                @foreach($accounts as $account)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-4
                                transition-all duration-200 hover:border-[#287857]/30 hover:bg-emerald-50/30">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-7 w-7 rounded-lg bg-linear-to-br from-emerald-400 to-emerald-600
                                        flex items-center justify-center text-white text-[10px] font-bold shrink-0">
                                {{ strtoupper(substr($account->account_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold text-[#17211c] truncate">{{ $account->account_name }}</p>
                                <p class="text-[11px] text-slate-400 truncate">{{ $account->shop_name }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-1 text-[11px] text-slate-500">
                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $account->completed_sales_count }} completed sales
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Low Stock --}}
        <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-[13px] font-bold text-[#17211c]">Low Stock Alert</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Products needing restock</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($lowStockProducts as $product)
                    <div class="flex items-center justify-between px-5 py-3 transition-colors duration-150 hover:bg-slate-50/70">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="h-2 w-2 rounded-full shrink-0
                                        {{ $product->current_stock <= 5 ? 'bg-rose-400' : 'bg-amber-400' }}">
                            </div>
                            <span class="text-sm text-slate-700 truncate">{{ $product->name }}</span>
                        </div>
                        <span class="ml-3 shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold
                                     {{ $product->current_stock <= 5
                                         ? 'bg-rose-50 text-rose-700'
                                         : 'bg-amber-50 text-amber-700' }}">
                            {{ $product->current_stock }} left
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-400">
                        All products are well stocked.
                    </div>
                @endforelse
            </div>
        </section>

    </div>

    @push('scripts')
        <script>
            const statusData = @json($statusChart);
            new Chart($('#statusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusData),
                    datasets: [{
                        data: Object.values(statusData),
                        backgroundColor: ['#f59e0b','#10b981','#f43f5e','#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 14, font: { size: 11 }, boxWidth: 10, boxHeight: 10 }
                        }
                    }
                }
            });
        </script>
    @endpush
</x-app-layout>
