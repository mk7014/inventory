<x-app-layout title="Stock Adjustment">

    {{-- ── Page header ────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Stock Adjustment</h1>
            <p class="mt-1 text-sm text-[#617068]">Manually increase or decrease on-hand stock, with a reason on every change</p>
        </div>
        @can('stock_adjustments.create')
        <button type="button" id="openAdjust"
                class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm
                       font-semibold text-white shadow-sm ring-1 ring-indigo-900/5 transition-all
                       duration-200 hover:bg-indigo-700 hover:shadow-md active:scale-[0.98]">
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Adjustment
        </button>
        @endcan
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#4f46e5 0%,rgba(79,70,229,0.15) 40%,transparent 100%);"></div>

    {{-- ── KPI cards (reflect the active filter) ──────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">

        {{-- Net change (feature card) --}}
        <div class="relative overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-indigo-900/10"
             style="background: linear-gradient(135deg,#4f46e5 0%,#3730a3 100%);">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-50/80">Net Change</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">
                    {{ $stats['net'] > 0 ? '+' : '' }}{{ number_format($stats['net']) }}
                </p>
                <p class="mt-1 text-[11px] font-medium text-indigo-50/70">Units, across all shown entries</p>
            </div>
        </div>

        {{-- Adjustments --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Adjustments</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['entries']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Entries logged</p>
        </div>

        {{-- Units added --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Units Added</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-700">+{{ number_format($stats['added']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Increases</p>
        </div>

        {{-- Units removed --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Units Removed</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-rose-600">−{{ number_format($stats['removed']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Decreases</p>
        </div>
    </div>

    {{-- ── History ────────────────────────────────────────────────── --}}
    <section class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm">

        {{-- Filters --}}
        <form method="get" class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Product</label>
                <select name="product_id" class="ppp-field">
                    <option value="">All products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) $filters['product_id'] === (string) $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:w-40">
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Type</label>
                <select name="type" class="ppp-field">
                    <option value="">All</option>
                    <option value="increase" @selected($filters['type'] === 'increase')>Increase</option>
                    <option value="decrease" @selected($filters['type'] === 'decrease')>Decrease</option>
                </select>
            </div>
            <div class="w-full lg:w-44">
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">From</label>
                <input type="date" name="from" value="{{ $filters['from'] }}" class="ppp-field">
            </div>
            <div class="w-full lg:w-44">
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">To</label>
                <input type="date" name="to" value="{{ $filters['to'] }}" class="ppp-field">
            </div>
            <div class="flex gap-2">
                <button class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900">
                    Filter
                </button>
                <a href="{{ route('stock-adjustments.index') }}"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="flex-1 overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3 text-center">Type</th>
                        <th class="px-5 py-3 text-center">Qty</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3">Reason</th>
                        <th class="px-5 py-3">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($adjustments as $adjustment)
                        @php $isIncrease = $adjustment->type === 'increase'; @endphp
                        <tr class="tbl-row align-middle">
                            <td class="whitespace-nowrap px-5 py-3 text-[12px] text-slate-500">
                                {{ $adjustment->created_at->format('d M Y') }}
                                <span class="block text-[11px] text-slate-300">{{ $adjustment->created_at->format('h:i A') }}</span>
                            </td>

                            <td class="px-5 py-3">
                                <span class="text-[13px] font-semibold text-slate-800">{{ $adjustment->product_name }}</span>
                                @unless($adjustment->product)
                                    <span class="ml-1 text-[10px] font-medium text-slate-300">(deleted)</span>
                                @endunless
                            </td>

                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold
                                             {{ $isIncrease ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isIncrease ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                    {{ $isIncrease ? 'Increase' : 'Decrease' }}
                                </span>
                            </td>

                            <td class="px-5 py-3 text-center">
                                <span class="text-[13px] font-bold {{ $isIncrease ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $isIncrease ? '+' : '−' }}{{ $adjustment->quantity }}
                                </span>
                            </td>

                            {{-- Before → after snapshot taken at the moment of the change --}}
                            <td class="whitespace-nowrap px-5 py-3 text-center text-[12px]">
                                <span class="text-slate-400">{{ $adjustment->stock_before }}</span>
                                <span class="mx-1 text-slate-300">→</span>
                                <span class="font-semibold text-slate-700">{{ $adjustment->stock_after }}</span>
                            </td>

                            <td class="px-5 py-3">
                                <span class="text-[12px] font-medium text-slate-600">{{ $adjustment->reasonLabel() }}</span>
                                @if($adjustment->note)
                                    <span class="mt-0.5 block max-w-xs truncate text-[11px] text-slate-400"
                                          title="{{ $adjustment->note }}">{{ $adjustment->note }}</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-[12px] text-slate-500">
                                {{ $adjustment->creator?->name ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50">
                                        <svg class="h-7 w-7 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">No adjustments found</p>
                                        <p class="mt-0.5 text-[12px] text-slate-400">Manual stock changes will appear here.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($adjustments->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $adjustments->links() }}</div>
        @endif
    </section>

    @can('stock_adjustments.create')
    {{-- ── Slide-over drawer: New Adjustment ──────────────────────── --}}
    <div id="adjustDrawer" class="fixed inset-0 z-40 hidden">
        <div id="adjustBackdrop"
             class="absolute inset-0 bg-slate-900/40 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

        <div id="adjustPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white
                    shadow-2xl transition-transform duration-300 ease-in-out">

            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50">
                    <svg class="h-4.5 w-4.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-[14px] font-bold text-[#17211c]">New Adjustment</h2>
                    <p class="text-[11px] text-slate-400">Correct on-hand stock manually</p>
                </div>
                <button type="button" id="closeAdjust"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="post" action="{{ route('stock-adjustments.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

                    @if($errors->any())
                        <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3">
                            <ul class="space-y-1 text-[12px] font-medium text-rose-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Product --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Product <span class="text-rose-400">*</span>
                        </label>
                        <select name="product_id" id="aProduct" required class="ppp-field">
                            <option value="">Select a product…</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                        data-stock="{{ $product->current_stock }}"
                                        data-available="{{ $product->availableStock() }}"
                                        @selected((string) old('product_id') === (string) $product->id)>
                                    {{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Live stock context for the picked product --}}
                        <div id="aStockInfo" class="mt-2 hidden rounded-xl border border-slate-100 bg-slate-50/70 px-3 py-2">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-medium text-slate-400">On hand</span>
                                <span id="aOnHand" class="font-bold text-slate-700">0</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-[11px]">
                                <span class="font-medium text-slate-400">Free to remove</span>
                                <span id="aAvailable" class="font-bold text-slate-700">0</span>
                            </div>
                        </div>
                    </div>

                    {{-- Direction --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Adjustment Type <span class="text-rose-400">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="type-opt cursor-pointer">
                                <input type="radio" name="type" value="increase" class="peer sr-only"
                                       @checked(old('type', 'increase') === 'increase')>
                                <div class="flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white
                                            px-3 py-2.5 text-[13px] font-semibold text-slate-500 transition
                                            peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Increase
                                </div>
                            </label>
                            <label class="type-opt cursor-pointer">
                                <input type="radio" name="type" value="decrease" class="peer sr-only"
                                       @checked(old('type') === 'decrease')>
                                <div class="flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white
                                            px-3 py-2.5 text-[13px] font-semibold text-slate-500 transition
                                            peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                    </svg>
                                    Decrease
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Quantity <span class="text-rose-400">*</span>
                        </label>
                        <input name="quantity" id="aQty" type="number" min="1" step="1" required
                               value="{{ old('quantity') }}" placeholder="e.g. 5" class="ppp-field">
                        <p id="aPreview" class="mt-1.5 hidden text-[11px] font-medium"></p>
                    </div>

                    {{-- Reason (options swap with the direction) --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Reason <span class="text-rose-400">*</span>
                        </label>
                        <select name="reason" id="aReason" required class="ppp-field"></select>
                    </div>

                    {{-- Note --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Note</label>
                        <textarea name="note" id="aNote" rows="3" maxlength="500"
                                  placeholder="Optional — reference, shelf, who counted it…"
                                  class="ppp-field">{{ old('note') }}</textarea>
                    </div>
                </div>

                <div class="flex gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    <button type="button" id="cancelAdjust"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold
                                   text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5
                                   text-sm font-semibold text-white shadow-sm transition-all duration-200
                                   hover:bg-indigo-700 hover:shadow-md active:scale-[0.99]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const REASONS = {
            increase: @json($increaseReasons),
            decrease: @json($decreaseReasons),
        };
        const OLD_REASON = @json(old('reason'));

        const drawer   = document.getElementById('adjustDrawer');
        const backdrop = document.getElementById('adjustBackdrop');
        const panel    = document.getElementById('adjustPanel');
        const aProduct = document.getElementById('aProduct');
        const aQty     = document.getElementById('aQty');
        const aReason  = document.getElementById('aReason');
        const aPreview = document.getElementById('aPreview');
        const stockBox = document.getElementById('aStockInfo');

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

        function selectedType() {
            return document.querySelector('input[name="type"]:checked')?.value || 'increase';
        }

        // Reason options depend on the direction; keep the old value on a validation bounce.
        function renderReasons(preserve) {
            const list = REASONS[selectedType()] || {};
            const keep = preserve ? aReason.value : null;
            aReason.innerHTML = '';
            Object.entries(list).forEach(([value, label]) => {
                const opt = new Option(label, value);
                aReason.add(opt);
            });
            if (keep && list[keep]) aReason.value = keep;
        }

        // Show what the change will do before it is applied.
        function renderPreview() {
            const opt = aProduct.selectedOptions[0];
            const qty = parseInt(aQty.value, 10);

            if (!opt || !opt.value) {
                stockBox.classList.add('hidden');
                aPreview.classList.add('hidden');
                return;
            }

            const onHand    = parseInt(opt.dataset.stock, 10);
            const available = parseInt(opt.dataset.available, 10);
            document.getElementById('aOnHand').textContent = onHand;
            document.getElementById('aAvailable').textContent = available;
            stockBox.classList.remove('hidden');

            if (!qty || qty < 1) {
                aPreview.classList.add('hidden');
                return;
            }

            const increase = selectedType() === 'increase';
            const after = increase ? onHand + qty : onHand - qty;

            // Mirrors the server rule: a decrease may not cut into booked stock.
            if (!increase && qty > available) {
                aPreview.textContent = `Only ${available} unit(s) are free to remove — the rest is booked for shipped orders.`;
                aPreview.className = 'mt-1.5 text-[11px] font-medium text-rose-600';
            } else {
                aPreview.textContent = `Stock will go from ${onHand} to ${after}.`;
                aPreview.className = `mt-1.5 text-[11px] font-medium ${increase ? 'text-emerald-600' : 'text-slate-500'}`;
            }
            aPreview.classList.remove('hidden');
        }

        document.getElementById('openAdjust').addEventListener('click', openDrawer);
        document.getElementById('closeAdjust').addEventListener('click', closeDrawer);
        document.getElementById('cancelAdjust').addEventListener('click', closeDrawer);
        backdrop.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !drawer.classList.contains('hidden')) closeDrawer();
        });

        document.querySelectorAll('input[name="type"]').forEach((radio) => {
            radio.addEventListener('change', () => { renderReasons(true); renderPreview(); });
        });
        aProduct.addEventListener('change', renderPreview);
        aQty.addEventListener('input', renderPreview);

        // Initial paint — restores the submitted values if validation sent us back.
        renderReasons(false);
        if (OLD_REASON && REASONS[selectedType()][OLD_REASON]) aReason.value = OLD_REASON;
        renderPreview();

        @if($errors->any())
            openDrawer();
        @endif
    </script>
    @endpush
    @endcan

</x-app-layout>
