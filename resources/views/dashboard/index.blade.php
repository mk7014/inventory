<x-app-layout title="Dashboard">

    @php
        $money = fn ($value) => '৳ '.number_format((float) $value, 2);
        $hasData = $sales['orders'] > 0 || $funds['total'] > 0 || $spend['total'] > 0;
        // balances.index is admin-only; everyone else has their own wallet page.
        $fundsLink = $isAdmin ? route('balances.index') : route('balance.received');
    @endphp

    {{-- ── Header + range filter ──────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Dashboard</h1>
            <p class="mt-1 text-sm text-[#617068]">
                {{ $isAdmin ? 'Company-wide performance' : 'Your funds and expenses' }}
                <span class="mx-1 text-slate-300">·</span>
                <span class="font-medium text-slate-500">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</span>
            </p>
        </div>

        <form method="get" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">From</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="ppp-field w-40">
            </div>
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">To</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="ppp-field w-40">
            </div>
            <button class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900">
                Apply
            </button>
            <a href="{{ route('dashboard') }}"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#4f46e5 0%,rgba(79,70,229,0.15) 40%,transparent 100%);"></div>

    @unless ($hasData)

        {{-- ── Empty state ────────────────────────────────────────── --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white px-6 py-20 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50">
                <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-600">Nothing recorded in this period</p>
            <p class="mt-1 text-[13px] text-slate-400">Try a wider date range, or start by recording a sale or a requisition.</p>
            <a href="{{ route('dashboard', ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]) }}"
               class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                View this year
            </a>
        </div>

    @else

    {{-- ── Headline KPIs ──────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Net profit (feature card) --}}
        <div class="group relative overflow-hidden rounded-2xl p-5 text-white shadow-sm ring-1 ring-indigo-900/10
                    transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
             style="background: linear-gradient(135deg,#4f46e5 0%,#3730a3 100%);">
            <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-10 -left-6 h-28 w-28 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-50/80">Net Profit</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">{{ $money($profit['net_profit']) }}</p>
                <p class="mt-1 text-[11px] font-medium text-indigo-50/70">
                    {{ $profit['net_margin'] }}% margin · after cost &amp; expenses
                </p>
            </div>
        </div>

        <x-dashboard.kpi label="Gross Revenue" :value="$money($profit['revenue'])" tone="emerald"
                         hint="Delivered sales only"
                         icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1m9-5a9 9 0 11-18 0 9 9 0 0118 0z" />

        <x-dashboard.kpi label="Funds Given" :value="$money($funds['total'])" tone="sky"
                         :hint="$funds['transactions'].' transactions'"
                         icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />

        <x-dashboard.kpi label="Total Spent" :value="$money($spend['total'])" tone="rose"
                         :hint="$spend['transactions'].' transactions'"
                         icon="M19 14l-7 7m0 0l-7-7m7 7V3" />
    </div>

    {{-- ── 1 + 2. Employee fund & expense summaries ───────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[14px] font-bold text-[#17211c]">Employee Fund Summary</h2>
                        <p class="text-[11px] text-slate-400">Money given to employees, by source</p>
                    </div>
                </div>
                <a href="{{ $fundsLink }}"
                   class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">
                    Quick View
                </a>
            </div>

            <div class="mt-4 flex flex-wrap items-baseline gap-x-2">
                <p class="text-2xl font-bold tracking-tight text-[#17211c]">{{ $money($funds['total']) }}</p>
                <span class="text-[11px] font-medium text-slate-400">across {{ $funds['transactions'] }} transactions</span>
            </div>

            <x-dashboard.breakdown :items="$funds['items']" tone="sky"
                                   empty="No funds were given in this period." />
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM9 9h6M9 13h6"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-[14px] font-bold text-[#17211c]">Employee Expense Summary</h2>
                        <p class="text-[11px] text-slate-400">How those funds were spent</p>
                    </div>
                </div>
                <a href="{{ route('expenses.index') }}"
                   class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">
                    Quick View
                </a>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total</p>
                    <p class="mt-1 truncate text-[15px] font-bold text-[#17211c]">{{ $money($spend['total']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Product</p>
                    <p class="mt-1 truncate text-[15px] font-bold text-indigo-700">{{ $money($spend['product_total']) }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Other</p>
                    <p class="mt-1 truncate text-[15px] font-bold text-amber-700">{{ $money($spend['other_total']) }}</p>
                </div>
            </div>

            <x-dashboard.breakdown :items="$spend['items']" tone="rose"
                                   empty="No spending recorded in this period." />
        </section>
    </div>

    {{-- ── 3 + 4. Sales & delivered ───────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-[14px] font-bold text-[#17211c]">Sales Analytics</h2>
                    <p class="text-[11px] text-slate-400">All orders in the selected range</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total Sales</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ $money($sales['amount']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Orders</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ number_format($sales['orders']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Avg Order Value</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ $money($sales['average_order_value']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Units Sold</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ number_format($sales['quantity']) }}</p>
                </div>
            </div>

            {{-- Delivered revenue by period — always "to date", independent of the filter. --}}
            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach (['daily' => 'Today', 'weekly' => 'This Week', 'monthly' => 'This Month', 'yearly' => 'This Year'] as $key => $label)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 px-3 py-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                        <p class="mt-0.5 truncate text-[13px] font-bold text-slate-700">{{ $money($sales['periods'][$key]) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <p class="mb-2.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Breakdown by Status</p>
                    <div class="space-y-2">
                        @foreach ($sales['statuses'] as $status)
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-bold {{ $status['badge'] }}">
                                    {{ $status['label'] }}
                                </span>
                                <span class="text-right text-[12px]">
                                    <span class="font-bold text-slate-700">{{ $money($status['amount']) }}</span>
                                    <span class="ml-1 text-slate-400">{{ $status['orders'] }} ord · {{ $status['percent'] }}%</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="mb-2.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status Distribution</p>
                    <div class="h-40"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-[14px] font-bold text-[#17211c]">Delivered Sales</h2>
                    <p class="text-[11px] text-slate-400">Fulfilled &amp; realised</p>
                </div>
            </div>

            <p class="mt-4 text-2xl font-bold tracking-tight text-emerald-700">{{ $money($delivered['revenue']) }}</p>

            <dl class="mt-4 space-y-2.5 text-[12px]">
                <div class="flex items-center justify-between">
                    <dt class="font-medium text-slate-400">Delivered orders</dt>
                    <dd class="font-bold text-slate-700">{{ number_format($delivered['orders']) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="font-medium text-slate-400">Units delivered</dt>
                    <dd class="font-bold text-slate-700">{{ number_format($delivered['quantity']) }}</dd>
                </div>
            </dl>

            <div class="mt-4">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="font-medium text-slate-400">Delivery rate</span>
                    <span class="font-bold text-emerald-700">{{ $delivered['order_percent'] }}%</span>
                </div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-700"
                         style="width: {{ min(100, $delivered['order_percent']) }}%"></div>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    {{ $delivered['orders'] }} of {{ $sales['orders'] }} orders delivered
                </p>
            </div>
        </section>
    </div>

    {{-- ── 5. Profit analytics ────────────────────────────────────── --}}
    <section class="mb-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </span>
            <div>
                <h2 class="text-[14px] font-bold text-[#17211c]">Profit Analytics</h2>
                <p class="text-[11px] text-slate-400">
                    Product cost is the weighted average of what each item actually cost to buy
                </p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <x-dashboard.figure label="Gross Revenue" :value="$money($profit['revenue'])" tone="slate" />
            <x-dashboard.figure label="Product Cost" :value="'− '.$money($profit['product_cost'])" tone="rose" />
            <x-dashboard.figure label="Gross Profit" :value="$money($profit['gross_profit'])"
                                :hint="$profit['gross_margin'].'% margin'" tone="emerald" />
            <x-dashboard.figure label="Operating Exp." :value="'− '.$money($profit['operating_expenses'])" tone="amber" />
            <x-dashboard.figure label="Net Profit" :value="$money($profit['net_profit'])" tone="indigo" />
            <x-dashboard.figure label="Net Margin" :value="$profit['net_margin'].'%'" tone="indigo" />
        </div>
    </section>

    {{-- ── 7. Charts ──────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">Revenue, Expenses &amp; Profit</h2>
            <p class="mb-3 text-[11px] text-slate-400">Last 12 months</p>
            <div class="h-60"><canvas id="trendChart"></canvas></div>
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">Purchases vs Sales</h2>
            <p class="mb-3 text-[11px] text-slate-400">Monthly comparison</p>
            <div class="h-60"><canvas id="compareChart"></canvas></div>
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">Delivered Sales Trend</h2>
            <p class="mb-3 text-[11px] text-slate-400">Daily revenue across the selected range</p>
            <div class="h-60"><canvas id="salesTrendChart"></canvas></div>
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">Expense Distribution</h2>
            <p class="mb-3 text-[11px] text-slate-400">By category</p>
            @if (empty($expenseCategories['items']))
                <div class="flex h-60 flex-col items-center justify-center gap-2 text-center">
                    <svg class="h-9 w-9 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    </svg>
                    <p class="text-[12px] font-medium text-slate-400">No categorised expenses yet</p>
                </div>
            @else
                <div class="h-60"><canvas id="expenseChart"></canvas></div>
            @endif
        </section>
    </div>

    {{-- ── Operational lists ──────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="mb-3 text-[14px] font-bold text-[#17211c]">Recent Requisitions</h2>
            @forelse ($recentRequisitions as $requisition)
                <a href="{{ route('requisitions.show', $requisition) }}"
                   class="-mx-2 flex items-center justify-between gap-3 rounded-xl px-2 py-2.5 transition hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-semibold text-slate-700">{{ $requisition->requisition_number }}</p>
                        <p class="truncate text-[11px] text-slate-400">{{ $requisition->employee?->name ?? '—' }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="text-[13px] font-bold text-slate-700">{{ $money($requisition->total_amount) }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-600">
                            {{ $requisition->status }}
                        </span>
                    </div>
                </a>
            @empty
                <p class="py-10 text-center text-[12px] text-slate-400">No requisitions yet.</p>
            @endforelse
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-[14px] font-bold text-[#17211c]">Low Stock</h2>
            @forelse ($lowStock as $product)
                <div class="-mx-2 flex items-center justify-between gap-2 rounded-xl px-2 py-2 transition hover:bg-slate-50">
                    <p class="truncate text-[12px] font-medium text-slate-600">{{ $product->name }}</p>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold
                                 {{ $product->current_stock <= 0 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $product->current_stock }}
                    </span>
                </div>
            @empty
                <p class="py-10 text-center text-[12px] text-slate-400">Stock levels are healthy.</p>
            @endforelse
        </section>
    </div>

    @push('scripts')
    <script>
        // Chart.js is already loaded globally by the layout. Every series below is rendered
        // server-side from aggregate SQL — nothing is recomputed in the browser.
        const PALETTE = ['#4f46e5', '#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#14b8a6', '#64748b'];
        const money = (value) => '৳ ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#94a3b8';

        const currencyAxis = { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v) => '৳ ' + Number(v).toLocaleString() } };
        const categoryAxis = { grid: { display: false }, border: { display: false } };
        const legendBottom = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, padding: 14 } };
        const currencyTooltip = { callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${money(ctx.parsed.y)}` } };

        const trend = @json($trend);
        const salesTrend = @json($salesTrend);
        const statuses = @json($sales['statuses']);
        const expenseCategories = @json($expenseCategories['items']);

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [
                    { label: 'Revenue', data: trend.revenue, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
                    { label: 'Expenses', data: trend.expenses, borderColor: '#f43f5e', tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
                    { label: 'Profit', data: trend.profit, borderColor: '#4f46e5', borderDash: [4, 3], tension: 0.35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: legendBottom, tooltip: currencyTooltip },
                scales: { y: currencyAxis, x: categoryAxis },
            },
        });

        new Chart(document.getElementById('compareChart'), {
            type: 'bar',
            data: {
                labels: trend.labels,
                datasets: [
                    { label: 'Purchases', data: trend.purchases, backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 14 },
                    { label: 'Sales', data: trend.revenue, backgroundColor: '#4f46e5', borderRadius: 4, maxBarThickness: 14 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: legendBottom, tooltip: currencyTooltip },
                scales: { y: currencyAxis, x: categoryAxis },
            },
        });

        new Chart(document.getElementById('salesTrendChart'), {
            type: 'line',
            data: {
                labels: salesTrend.labels,
                datasets: [{
                    label: 'Delivered revenue', data: salesTrend.revenue,
                    borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.10)',
                    fill: true, tension: 0.3, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: currencyTooltip },
                scales: { y: currencyAxis, x: { ...categoryAxis, ticks: { maxTicksLimit: 10 } } },
            },
        });

        if (statuses.length) {
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: statuses.map((row) => row.label),
                    datasets: [{ data: statuses.map((row) => row.orders), backgroundColor: PALETTE, borderWidth: 0, hoverOffset: 6 }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: {
                        legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 6, padding: 10 } },
                        tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${ctx.parsed} orders` } },
                    },
                },
            });
        }

        if (expenseCategories.length) {
            new Chart(document.getElementById('expenseChart'), {
                type: 'doughnut',
                data: {
                    labels: expenseCategories.map((row) => row.label),
                    datasets: [{ data: expenseCategories.map((row) => row.total), backgroundColor: PALETTE, borderWidth: 0, hoverOffset: 6 }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: {
                        legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 6, padding: 10 } },
                        tooltip: { callbacks: { label: (ctx) => ` ${ctx.label}: ${money(ctx.parsed)}` } },
                    },
                },
            });
        }
    </script>
    @endpush

    @endunless
</x-app-layout>
