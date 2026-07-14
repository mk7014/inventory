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

        {{-- Profit you keep (feature card) — jumps to the plain-English breakdown below --}}
        <a href="#profit-explainer"
           class="group relative block overflow-hidden rounded-2xl p-5 text-white shadow-sm ring-1 ring-indigo-900/10
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
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-50/80">Profit You Keep</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">{{ $money($profit['net_profit']) }}</p>
                <p class="mt-1 text-[11px] font-medium text-indigo-50/70">
                    What is left after everything is paid
                </p>
                <p class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-white/90 underline-offset-2 group-hover:underline">
                    See how this is calculated
                    <svg class="h-3 w-3 transition-transform group-hover:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7"/>
                    </svg>
                </p>
            </div>
        </a>

        <x-dashboard.kpi label="Money You Kept" :value="$money($profit['revenue'])" tone="emerald"
                         hint="Sales, after returns were refunded" metric="revenue"
                         icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1m9-5a9 9 0 11-18 0 9 9 0 0118 0z" />

        <x-dashboard.kpi label="Money Given Back (Returns)" :value="$money($returns['value'])" tone="amber"
                         :hint="$returns['quantity'].' item(s) came back · '.$returns['rate'].'% of sales'" metric="returns"
                         icon="M3 10h10a4 4 0 014 4v1M3 10l4-4M3 10l4 4" />

        <x-dashboard.kpi label="Money Given to Staff" :value="$money($funds['total'])" tone="sky"
                         :hint="$funds['transactions'].' payments'" metric="funds"
                         icon="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
    </div>

    {{-- Second row: what staff spent, kept out of the headline four --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.kpi label="Money Staff Spent" :value="$money($spend['total'])" tone="rose"
                         :hint="$spend['transactions'].' transactions'" metric="spend"
                         icon="M19 14l-7 7m0 0l-7-7m7 7V3" />

        <x-dashboard.kpi label="Total Ordered (before returns)" :value="$money($profit['gross_sales'])" tone="slate"
                         hint="Everything customers ordered and received" metric="orders"
                         icon="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />

        <x-dashboard.kpi label="Damaged Returns (loss)" :value="$money($profit['damaged_loss'])" tone="rose"
                         :hint="$returns['damaged_quantity'].' item(s) could not be resold'" metric="returns"
                         icon="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />

        <x-dashboard.kpi label="Running Costs" :value="$money($profit['operating_expenses'])" tone="amber"
                         hint="Transport, food, office and so on" metric="expenses"
                         icon="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
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
                        <h2 class="text-[14px] font-bold text-[#17211c]">Money Given to Staff</h2>
                        <p class="text-[11px] text-slate-400">Where the money came from</p>
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
                        <h2 class="text-[14px] font-bold text-[#17211c]">Where Staff Money Went</h2>
                        <p class="text-[11px] text-slate-400">Buying products vs day-to-day costs</p>
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
                    <h2 class="text-[14px] font-bold text-[#17211c]">Your Orders</h2>
                    <p class="text-[11px] text-slate-400">Every order, whatever its status</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Value of all orders</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ $money($sales['amount']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Orders</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ number_format($sales['orders']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Average per order</p>
                    <p class="mt-1 text-lg font-bold text-[#17211c]">{{ $money($sales['average_order_value']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Items sold</p>
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
                    <p class="mb-2.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        Every Order, by Status
                    </p>
                    <div class="space-y-1.5">
                        @foreach ($sales['statuses'] as $status)
                            {{-- Each status opens the exact orders that sit in it --}}
                            <button type="button"
                                    class="js-metric group flex w-full items-start justify-between gap-2 rounded-lg
                                           px-2 py-1.5 text-left transition hover:bg-slate-50"
                                    data-metric="orders" data-status="{{ $status['status'] }}">
                                <span class="min-w-0">
                                    <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-bold {{ $status['badge'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                    <span class="mt-1 block truncate text-[11px] text-slate-400 group-hover:text-slate-500">
                                        {{ $status['meaning'] }}
                                    </span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="block text-[12px] font-bold text-slate-700">{{ $money($status['amount']) }}</span>
                                    <span class="block text-[11px] text-slate-400">
                                        {{ $status['orders'] }} order(s) · {{ $status['quantity'] }} item(s) · {{ $status['percent'] }}%
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-2 text-[11px] text-slate-300">Click any status to see the orders inside it.</p>
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
                    <h2 class="text-[14px] font-bold text-[#17211c]">Completed Orders</h2>
                    <p class="text-[11px] text-slate-400">Delivered — the money is real</p>
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

    {{-- ── 5. How profit is made — plain-English walkthrough ───────── --}}
    <section id="profit-explainer" class="mb-6 scroll-mt-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </span>
            <div>
                <h2 class="text-[14px] font-bold text-[#17211c]">How Your Profit Is Made</h2>
                <p class="text-[11px] text-slate-400">Follow the money, step by step. Click any step to see the records behind it.</p>
            </div>
        </div>

        @php
            // Bar widths are relative to the biggest number in the walk (gross sales),
            // so each step visibly shrinks the pile.
            $base = max($profit['gross_sales'], 1);
            $scale = fn ($value) => $value == 0 ? 0 : max(2, min(100, abs($value) / $base * 100));
        @endphp

        <div class="mt-5 space-y-2.5">

            {{-- Step 1 — everything customers ordered --}}
            <button type="button" class="js-metric group flex w-full items-center gap-3 rounded-xl border border-slate-100
                                          bg-slate-50/70 p-3 text-left transition hover:border-emerald-200 hover:bg-emerald-50/50"
                    data-metric="orders">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-[11px] font-bold text-emerald-700">1</span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-baseline justify-between gap-x-2">
                        <span class="text-[13px] font-bold text-slate-700">Money customers paid you</span>
                        <span class="text-[14px] font-bold text-emerald-700">{{ $money($profit['gross_sales']) }}</span>
                    </span>
                    <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70">
                        <span class="block h-full rounded-full bg-emerald-500" style="width: {{ $scale($profit['gross_sales']) }}%"></span>
                    </span>
                    <span class="mt-1 block text-[11px] text-slate-400">
                        Every order the customer actually received. Pending and cancelled orders are not money in the bank.
                    </span>
                </span>
            </button>

            {{-- Step 2 — returns --}}
            <button type="button" class="js-metric group flex w-full items-center gap-3 rounded-xl border border-slate-100
                                          bg-slate-50/70 p-3 text-left transition hover:border-amber-200 hover:bg-amber-50/50"
                    data-metric="returns">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-[11px] font-bold text-amber-700">2</span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-baseline justify-between gap-x-2">
                        <span class="text-[13px] font-bold text-slate-700">Minus what you refunded for returns</span>
                        <span class="text-[14px] font-bold text-amber-700">− {{ $money($profit['returned_value']) }}</span>
                    </span>
                    <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70">
                        <span class="block h-full rounded-full bg-amber-500" style="width: {{ $scale($profit['returned_value']) }}%"></span>
                    </span>
                    <span class="mt-1 block text-[11px] text-slate-400">
                        {{ $returns['quantity'] }} item(s) came back and the money went back to the customer.
                        If a customer kept part of an order, you still keep the money for that part.
                    </span>
                </span>
            </button>

            {{-- Subtotal — money kept --}}
            <div class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/60 p-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-2">
                        <span class="text-[13px] font-bold text-emerald-800">Money you actually kept from sales</span>
                        <span class="text-[15px] font-bold text-emerald-800">{{ $money($profit['revenue']) }}</span>
                    </div>
                </div>
            </div>

            {{-- Step 3 — what the goods cost --}}
            <button type="button" class="js-metric group flex w-full items-center gap-3 rounded-xl border border-slate-100
                                          bg-slate-50/70 p-3 text-left transition hover:border-rose-200 hover:bg-rose-50/50"
                    data-metric="cost">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-[11px] font-bold text-rose-700">3</span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-baseline justify-between gap-x-2">
                        <span class="text-[13px] font-bold text-slate-700">Minus what those products cost you</span>
                        <span class="text-[14px] font-bold text-rose-600">− {{ $money($profit['product_cost']) }}</span>
                    </span>
                    <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70">
                        <span class="block h-full rounded-full bg-rose-500" style="width: {{ $scale($profit['product_cost']) }}%"></span>
                    </span>
                    <span class="mt-1 block text-[11px] text-slate-400">
                        Only for the goods the customer kept. Anything returned in good condition is back on the shelf, so it never cost you anything.
                    </span>
                </span>
            </button>

            {{-- Step 4 — damaged returns (only when there are any) --}}
            @if($profit['damaged_loss'] > 0)
                <button type="button" class="js-metric group flex w-full items-center gap-3 rounded-xl border border-rose-100
                                              bg-rose-50/50 p-3 text-left transition hover:border-rose-300 hover:bg-rose-50"
                        data-metric="returns">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-[11px] font-bold text-rose-700">4</span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-baseline justify-between gap-x-2">
                            <span class="text-[13px] font-bold text-slate-700">Minus goods returned damaged</span>
                            <span class="text-[14px] font-bold text-rose-600">− {{ $money($profit['damaged_loss']) }}</span>
                        </span>
                        <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70">
                            <span class="block h-full rounded-full bg-rose-600" style="width: {{ $scale($profit['damaged_loss']) }}%"></span>
                        </span>
                        <span class="mt-1 block text-[11px] text-slate-400">
                            {{ $returns['damaged_quantity'] }} item(s) came back broken — you refunded the customer AND cannot sell the goods again. A pure loss.
                        </span>
                    </span>
                </button>
            @endif

            {{-- Step 5 — running costs --}}
            <button type="button" class="js-metric group flex w-full items-center gap-3 rounded-xl border border-slate-100
                                          bg-slate-50/70 p-3 text-left transition hover:border-amber-200 hover:bg-amber-50/50"
                    data-metric="expenses">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-[11px] font-bold text-amber-700">
                    {{ $profit['damaged_loss'] > 0 ? 5 : 4 }}
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-baseline justify-between gap-x-2">
                        <span class="text-[13px] font-bold text-slate-700">Minus running costs</span>
                        <span class="text-[14px] font-bold text-amber-700">− {{ $money($profit['operating_expenses']) }}</span>
                    </span>
                    <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-200/70">
                        <span class="block h-full rounded-full bg-amber-500" style="width: {{ $scale($profit['operating_expenses']) }}%"></span>
                    </span>
                    <span class="mt-1 block text-[11px] text-slate-400">
                        Transport, food, office supplies and other day-to-day costs of running the business.
                    </span>
                </span>
            </button>

            {{-- Result --}}
            <div class="relative overflow-hidden rounded-xl p-4 text-white shadow-sm"
                 style="background: linear-gradient(135deg,#4f46e5 0%,#3730a3 100%);">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/10"></div>
                <div class="relative flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                    <div>
                        <p class="text-[13px] font-bold">= Profit you actually keep</p>
                        <p class="mt-0.5 text-[11px] text-indigo-50/75">
                            {{ $profit['net_margin'] }} taka kept from every 100 taka of sales
                        </p>
                    </div>
                    <p class="text-2xl font-bold tracking-tight">{{ $money($profit['net_profit']) }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── 7. Charts ──────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">Money In vs Money Out</h2>
            <p class="mb-3 text-[11px] text-slate-400">Last 12 months</p>
            <div class="h-60"><canvas id="trendChart"></canvas></div>
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">What You Bought vs What You Sold</h2>
            <p class="mb-3 text-[11px] text-slate-400">Monthly comparison</p>
            <div class="h-60"><canvas id="compareChart"></canvas></div>
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">Daily Sales</h2>
            <p class="mb-3 text-[11px] text-slate-400">Daily revenue across the selected range</p>
            <div class="h-60"><canvas id="salesTrendChart"></canvas></div>
        </section>

        <section class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <h2 class="text-[14px] font-bold text-[#17211c]">What Running Costs Go On</h2>
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

    {{-- ── Drill-down drawer: the records behind a figure ──────────── --}}
    <div id="detailDrawer" class="fixed inset-0 z-50 hidden">
        <div id="detailBackdrop"
             class="absolute inset-0 bg-slate-900/40 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

        <div id="detailPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-3xl translate-x-full flex-col bg-white
                    shadow-2xl transition-transform duration-300 ease-in-out">

            <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <h2 id="dtTitle" class="text-[15px] font-bold text-[#17211c]">Loading…</h2>
                    <p id="dtSubtitle" class="mt-0.5 text-[12px] text-slate-500"></p>
                </div>
                <button type="button" id="dtClose"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto">
                {{-- Loading skeleton --}}
                <div id="dtSkeleton" class="space-y-2 p-5">
                    @for ($i = 0; $i < 6; $i++)
                        <div class="h-9 animate-pulse rounded-lg bg-slate-100"></div>
                    @endfor
                </div>

                <table id="dtTable" class="hidden w-full text-left text-sm">
                    <thead class="sticky top-0 bg-slate-50/95 backdrop-blur">
                        <tr id="dtHead" class="border-b border-slate-100 text-[10px] font-semibold uppercase tracking-wider text-slate-400"></tr>
                    </thead>
                    <tbody id="dtBody" class="divide-y divide-slate-100"></tbody>
                </table>

                <div id="dtEmpty" class="hidden px-5 py-16 text-center">
                    <p class="text-sm font-semibold text-slate-500">No records in this period</p>
                    <p class="mt-1 text-[12px] text-slate-400">Try widening the date range at the top of the dashboard.</p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <span id="dtTotalLabel" class="text-[11px] font-semibold uppercase tracking-wider text-slate-400"></span>
                <span id="dtTotal" class="text-[15px] font-bold text-[#17211c]"></span>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // ── Drill-down: click a figure, see the rows it is made of ──────────────
        // The rows come from the same queries as the cards, so they always add up.
        const dtDrawer   = document.getElementById('detailDrawer');
        const dtBackdrop = document.getElementById('detailBackdrop');
        const dtPanel    = document.getElementById('detailPanel');
        const dtSkeleton = document.getElementById('dtSkeleton');
        const dtTable    = document.getElementById('dtTable');
        const dtEmpty    = document.getElementById('dtEmpty');

        const DETAIL_URL = @json(route('dashboard.details'));
        const RANGE = { from: @json($from->toDateString()), to: @json($to->toDateString()) };

        function openDetail() {
            dtDrawer.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                dtBackdrop.classList.remove('opacity-0');
                dtPanel.classList.remove('translate-x-full');
            });
        }

        function closeDetail() {
            dtBackdrop.classList.add('opacity-0');
            dtPanel.classList.add('translate-x-full');
            document.body.style.overflow = '';
            setTimeout(() => dtDrawer.classList.add('hidden'), 300);
        }

        async function loadDetail(metric, status = null) {
            // Show the drawer immediately with a skeleton — never a blank freeze.
            dtSkeleton.classList.remove('hidden');
            dtTable.classList.add('hidden');
            dtEmpty.classList.add('hidden');
            document.getElementById('dtTitle').textContent = 'Loading…';
            document.getElementById('dtSubtitle').textContent = '';
            document.getElementById('dtTotal').textContent = '';
            document.getElementById('dtTotalLabel').textContent = '';
            openDetail();

            const params = new URLSearchParams({ metric, from: RANGE.from, to: RANGE.to });
            if (status) params.set('status', status);

            try {
                const response = await fetch(`${DETAIL_URL}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) throw new Error(response.statusText);

                const data = await response.json();

                document.getElementById('dtTitle').textContent = data.title;
                document.getElementById('dtSubtitle').textContent = data.subtitle;
                document.getElementById('dtTotalLabel').textContent = data.total_label;
                document.getElementById('dtTotal').textContent =
                    '৳ ' + Number(data.total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                document.getElementById('dtHead').innerHTML = data.columns
                    .map((column, index) => {
                        const alignRight = index === data.columns.length - 1;
                        return `<th class="px-5 py-3 ${alignRight ? 'text-right' : ''}">${column}</th>`;
                    })
                    .join('');

                document.getElementById('dtBody').innerHTML = data.rows
                    .map((row) => '<tr class="tbl-row align-middle">' + row
                        .map((cell, index) => {
                            const last = index === row.length - 1;
                            return `<td class="px-5 py-2.5 text-[12px] ${last ? 'text-right font-bold text-slate-700' : 'text-slate-600'}">${cell}</td>`;
                        })
                        .join('') + '</tr>')
                    .join('');

                dtSkeleton.classList.add('hidden');
                (data.rows.length ? dtTable : dtEmpty).classList.remove('hidden');
            } catch (error) {
                dtSkeleton.classList.add('hidden');
                dtEmpty.classList.remove('hidden');
                document.getElementById('dtTitle').textContent = 'Could not load the details';
                document.getElementById('dtSubtitle').textContent = 'Please try again.';
            }
        }

        document.querySelectorAll('.js-metric').forEach((element) => {
            element.addEventListener('click', () => loadDetail(element.dataset.metric, element.dataset.status || null));
        });

        document.getElementById('dtClose').addEventListener('click', closeDetail);
        dtBackdrop.addEventListener('click', closeDetail);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dtDrawer.classList.contains('hidden')) closeDetail();
        });

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
