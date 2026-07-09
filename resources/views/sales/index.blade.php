<x-app-layout title="Sales">

    {{-- ── Page header with New Sale trigger ──────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Sales</h1>
            <p class="mt-1 text-sm text-[#617068]">Record completed Daraz sales from stock or new purchase</p>
        </div>
        <button type="button" id="openSaleDrawer"
                class="group inline-flex items-center gap-2 rounded-xl bg-[#287857] px-4 py-2.5 text-sm
                       font-semibold text-white shadow-sm ring-1 ring-emerald-900/5 transition-all
                       duration-200 hover:bg-[#1f6046] hover:shadow-md active:scale-[0.98]">
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Sale
        </button>
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#287857 0%,rgba(40,120,87,0.15) 40%,transparent 100%);"></div>

    {{-- ── KPI stat cards ─────────────────────────────────────────── --}}
    @php
        $stockPct = $stats['total_sales'] > 0 ? round($stats['from_stock'] / $stats['total_sales'] * 100) : 0;
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">

        {{-- Total revenue (feature card) --}}
        <div class="relative overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-emerald-900/10"
             style="background: linear-gradient(135deg,#287857 0%,#1f6046 100%);">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-50/80">Total Revenue</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">৳ {{ number_format($stats['total_revenue'], 2) }}</p>
                <p class="mt-1 text-[11px] font-medium text-emerald-50/70">Across all recorded sales</p>
            </div>
        </div>

        {{-- Total sales --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Sales</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['total_sales']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Completed orders</p>
        </div>

        {{-- Units sold --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Units Sold</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['total_units']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Total quantity moved</p>
        </div>

        {{-- Source split --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Source Split</p>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <p class="text-2xl font-bold tracking-tight text-[#17211c]">{{ $stockPct }}%</p>
                <span class="text-[11px] font-medium text-slate-400">from stock</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-indigo-400" style="width: {{ $stockPct }}%;"></div>
            </div>
            <div class="mt-1.5 flex items-center justify-between text-[10px] font-medium">
                <span class="text-indigo-500">{{ $stats['from_stock'] }} stock</span>
                <span class="text-amber-500">{{ $stats['new_purchase'] }} new</span>
            </div>
        </div>
    </div>

    {{-- ── Sales table (full width) ───────────────────────────────── --}}
    <section class="flex flex-col rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">

        {{-- Table header + client-side search --}}
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Sales History</h2>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $sales->total() }} total records</p>
            </div>
            <div class="relative w-full sm:w-64">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-300"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="saleSearch" type="text" placeholder="Search product or account…"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-9 pr-3 text-[13px]
                              text-slate-700 placeholder-slate-300 transition
                              focus:border-[#287857] focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto flex-1">
            <table class="w-full min-w-180 text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Account</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3 text-center">Qty</th>
                        <th class="px-5 py-3 text-right">Unit Price</th>
                        <th class="px-5 py-3 text-right">Revenue</th>
                        <th class="px-5 py-3">Source</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody id="saleTableBody" class="divide-y divide-slate-100">
                    @forelse($sales as $sale)
                        <tr class="tbl-row"
                            data-search="{{ strtolower($sale->product_name . ' ' . $sale->account->account_name) }}">
                            <td class="whitespace-nowrap px-5 py-3">
                                <div class="text-[12px] font-medium text-slate-700">{{ $sale->sold_date->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $sale->sold_date->format('l') }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg
                                                 bg-slate-100 text-[10px] font-bold uppercase text-slate-500">
                                        {{ \Illuminate\Support\Str::of($sale->account->account_name)->substr(0, 2) }}
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-700">{{ $sale->account->account_name }}</span>
                                </div>
                            </td>
                            <td class="max-w-56 px-5 py-3">
                                <span class="block truncate text-[12px] font-medium text-slate-700"
                                      title="{{ $sale->product_name }}">{{ $sale->product_name }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2 py-0.5
                                             text-[12px] font-semibold text-slate-600">{{ $sale->quantity }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-[12px] text-slate-500">
                                ৳ {{ number_format($sale->selling_price, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <span class="text-[13px] font-bold text-emerald-700">
                                    ৳ {{ number_format($sale->quantity * $sale->selling_price, 2) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($sale->source === 'stock')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold text-indigo-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span> Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> New Purchase
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @include('partials.status', ['status' => $sale->status])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50">
                                        <svg class="h-7 w-7 text-slate-200" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">No sales yet</p>
                                        <p class="mt-0.5 text-[12px] text-slate-400">Click “New Sale” to record your first sale.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    {{-- No-results row (shown by search JS) --}}
                    <tr id="saleNoResults" class="hidden">
                        <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-400">
                            No sales match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sales->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $sales->links() }}</div>
        @endif
    </section>

    {{-- ── Slide-over drawer: New Sale ────────────────────────────── --}}
    <div id="saleDrawer" class="fixed inset-0 z-40 hidden">
        {{-- Backdrop --}}
        <div id="saleDrawerBackdrop"
             class="absolute inset-0 bg-slate-900/40 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

        {{-- Panel --}}
        <div id="saleDrawerPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white
                    shadow-2xl transition-transform duration-300 ease-in-out">

            {{-- Drawer header --}}
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                    <svg class="h-4.5 w-4.5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-[14px] font-bold text-[#17211c]">New Sale</h2>
                    <p class="text-[11px] text-slate-400">Fill in the details below</p>
                </div>
                <button type="button" id="closeSaleDrawer"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Drawer body (scrollable form) --}}
            <form method="post" action="{{ route('sales.store') }}" id="saleForm"
                  class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

                    {{-- Daraz Account --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Daraz Account
                        </label>
                        <select name="daraz_account_id" class="ppp-field" required>
                            <option value="">Select account…</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Source toggle --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Sale Source
                        </label>
                        <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                            <label class="source-tab flex-1 cursor-pointer">
                                <input type="radio" name="source" value="new_purchase" class="sr-only" checked>
                                <span class="flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-[12px] font-semibold
                                             transition-all duration-200 bg-amber-500 text-white shadow-sm">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    New Purchase
                                </span>
                            </label>
                            <label class="source-tab flex-1 cursor-pointer">
                                <input type="radio" name="source" value="stock" id="saleSource" class="sr-only">
                                <span class="flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-[12px] font-semibold
                                             transition-all duration-200 text-slate-500 hover:text-slate-700">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    From Stock
                                </span>
                            </label>
                        </div>
                    </div>

                    {{-- Product select --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Product
                        </label>
                        <select name="product_id" id="saleProduct" class="ppp-field">
                            <option value="">Select product…</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <div id="stockResult" class="mt-2 hidden items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-2">
                            <svg class="h-3.5 w-3.5 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span id="stockText" class="text-[11px] font-medium text-indigo-700"></span>
                        </div>
                    </div>

                    {{-- Manual product name --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Product Name
                            <span class="ml-1 font-normal normal-case text-slate-300">(if not in list)</span>
                        </label>
                        <input name="product_name" class="ppp-field" placeholder="Enter manually…">
                    </div>

                    {{-- Qty + Price row --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Quantity
                            </label>
                            <input name="quantity" id="saleQty" type="number" min="1" value="1" class="ppp-field" required>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Unit Price (৳)
                            </label>
                            <input name="selling_price" id="salePrice" type="number" min="0.01" step="0.01"
                                   class="ppp-field" placeholder="0.00" required>
                        </div>
                    </div>

                    {{-- Revenue preview --}}
                    <div id="revenuePreview"
                         class="hidden items-center justify-between rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                        <span class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            Estimated Revenue
                        </span>
                        <span id="revenueAmt" class="text-base font-bold text-emerald-700">৳ 0.00</span>
                    </div>

                    {{-- Sale date --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Sale Date
                        </label>
                        <input name="sold_date" type="date" value="{{ now()->toDateString() }}" class="ppp-field" required>
                    </div>
                </div>

                {{-- Drawer footer --}}
                <div class="flex gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    <button type="button" id="cancelSaleDrawer"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold
                                   text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#287857] px-4 py-2.5
                                   text-sm font-semibold text-white shadow-sm transition-all duration-200
                                   hover:bg-[#1f6046] hover:shadow-md active:scale-[0.99]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Sale
                    </button>
                </div>
            </form>
        </div>
    </div>


    @push('scripts')
    <script>
        // ── Drawer open/close ──────────────────────────────────────
        const drawer   = document.getElementById('saleDrawer');
        const backdrop = document.getElementById('saleDrawerBackdrop');
        const panel    = document.getElementById('saleDrawerPanel');

        function openDrawer() {
            drawer.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('translate-x-full');
            });
        }
        function closeDrawer() {
            backdrop.classList.add('opacity-0');
            panel.classList.add('translate-x-full');
            document.body.style.overflow = '';
            setTimeout(() => drawer.classList.add('hidden'), 300);
        }

        document.getElementById('openSaleDrawer').addEventListener('click', openDrawer);
        document.getElementById('closeSaleDrawer').addEventListener('click', closeDrawer);
        document.getElementById('cancelSaleDrawer').addEventListener('click', closeDrawer);
        backdrop.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !drawer.classList.contains('hidden')) closeDrawer();
        });

        // Re-open drawer automatically if there were validation errors
        @if($errors->any())
            openDrawer();
        @endif

        // ── Source radio toggle styling ────────────────────────────
        $('input[name="source"]').on('change', function () {
            $('.source-tab span')
                .removeClass('bg-amber-500 bg-indigo-500 text-white shadow-sm')
                .addClass('text-slate-500');
            const span = $(this).closest('label').find('span');
            const isStock = $(this).val() === 'stock';
            span.removeClass('text-slate-500')
                .addClass((isStock ? 'bg-indigo-500' : 'bg-amber-500') + ' text-white shadow-sm');
        });

        // ── Stock check ────────────────────────────────────────────
        $('#saleProduct').on('change', function () {
            if (!this.value) {
                $('#stockResult').addClass('hidden').removeClass('flex');
                return;
            }
            $.get('{{ route('sales.stock-check') }}', { product_id: this.value }, function (data) {
                $('#stockText').text('Available stock: ' + data.stock + ' units');
                $('#stockResult').removeClass('hidden').addClass('flex');
            });
        });

        // ── Revenue preview ────────────────────────────────────────
        function updateRevenue() {
            const qty   = Number($('#saleQty').val() || 0);
            const price = Number($('#salePrice').val() || 0);
            const rev   = qty * price;
            if (rev > 0) {
                $('#revenueAmt').text('৳ ' + rev.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#revenuePreview').removeClass('hidden').addClass('flex');
            } else {
                $('#revenuePreview').addClass('hidden').removeClass('flex');
            }
        }
        $('#saleQty, #salePrice').on('input', updateRevenue);

        // ── Client-side search ─────────────────────────────────────
        $('#saleSearch').on('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            $('#saleTableBody tr.tbl-row').each(function () {
                const match = $(this).data('search').indexOf(q) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#saleNoResults').toggleClass('hidden', visible !== 0 || q === '');
        });
    </script>
    @endpush
</x-app-layout>
