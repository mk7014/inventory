<x-app-layout title="Returns">

    {{-- ── Page header with New Return trigger ────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Returns</h1>
            <p class="mt-1 text-sm text-[#617068]">Good returns increase stock; damaged returns are tracked as loss</p>
        </div>
        <button type="button" id="openReturnDrawer"
                class="group inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm
                       font-semibold text-white shadow-sm ring-1 ring-rose-900/5 transition-all
                       duration-200 hover:bg-rose-700 hover:shadow-md active:scale-[0.98]">
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Return
        </button>
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#e11d48 0%,rgba(225,29,72,0.15) 40%,transparent 100%);"></div>

    {{-- ── KPI stat cards ─────────────────────────────────────────── --}}
    @php
        $goodPct = $stats['total_returns'] > 0 ? round($stats['good'] / $stats['total_returns'] * 100) : 0;
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">

        {{-- Total returns (feature card) --}}
        <div class="relative overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-rose-900/10"
             style="background: linear-gradient(135deg,#e11d48 0%,#9f1239 100%);">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-rose-50/80">Total Returns</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">{{ number_format($stats['total_returns']) }}</p>
                <p class="mt-1 text-[11px] font-medium text-rose-50/70">Across all records</p>
            </div>
        </div>

        {{-- Units returned --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Units Returned</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['total_units']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Total quantity</p>
        </div>

        {{-- Good (restocked) --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Good · Restocked</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-700">{{ number_format($stats['good']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Added back to stock</p>
        </div>

        {{-- Damaged (loss) --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Damaged · Loss</p>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <p class="text-2xl font-bold tracking-tight text-rose-700">{{ number_format($stats['damaged']) }}</p>
                <span class="text-[11px] font-medium text-slate-400">{{ $goodPct }}% good</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-rose-100">
                <div class="h-full rounded-full bg-emerald-400" style="width: {{ $goodPct }}%;"></div>
            </div>
        </div>
    </div>

    {{-- ── Returns table (full width) ─────────────────────────────── --}}
    <section class="flex flex-col rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">

        {{-- Table header + client-side search --}}
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Return History</h2>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $returns->total() }} records</p>
            </div>
            <div class="relative w-full sm:w-64">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-300"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="returnSearch" type="text" placeholder="Search product or reason…"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-9 pr-3 text-[13px]
                              text-slate-700 placeholder-slate-300 transition
                              focus:border-rose-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/10">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto flex-1">
            <table class="w-full min-w-160 text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3 text-center">Qty</th>
                        <th class="px-5 py-3">Condition</th>
                        <th class="px-5 py-3">Reason</th>
                    </tr>
                </thead>
                <tbody id="returnTableBody" class="divide-y divide-slate-100">
                    @forelse($returns as $return)
                        <tr class="tbl-row"
                            data-search="{{ strtolower($return->product_name . ' ' . $return->reason) }}">
                            <td class="whitespace-nowrap px-5 py-3">
                                <div class="text-[12px] font-medium text-slate-700">{{ $return->return_date->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $return->return_date->format('l') }}</div>
                            </td>
                            <td class="max-w-56 px-5 py-3">
                                <span class="block truncate text-[12px] font-medium text-slate-700"
                                      title="{{ $return->product_name }}">{{ $return->product_name }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2 py-0.5
                                             text-[12px] font-semibold text-slate-600">{{ $return->quantity }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($return->condition === 'good')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Good
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-semibold text-rose-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span> Damaged
                                    </span>
                                @endif
                            </td>
                            <td class="max-w-64 px-5 py-3">
                                <span class="block truncate text-[12px] text-slate-500" title="{{ $return->reason }}">
                                    {{ $return->reason ?: '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50">
                                        <svg class="h-7 w-7 text-slate-200" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">No returns yet</p>
                                        <p class="mt-0.5 text-[12px] text-slate-400">Click “New Return” to log a returned item.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    {{-- No-results row (shown by search JS) --}}
                    <tr id="returnNoResults" class="hidden">
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                            No returns match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($returns->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $returns->links() }}</div>
        @endif
    </section>

    {{-- ── Slide-over drawer: New Return ──────────────────────────── --}}
    <div id="returnDrawer" class="fixed inset-0 z-40 hidden">
        {{-- Backdrop --}}
        <div id="returnDrawerBackdrop"
             class="absolute inset-0 bg-slate-900/40 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

        {{-- Panel --}}
        <div id="returnDrawerPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white
                    shadow-2xl transition-transform duration-300 ease-in-out">

            {{-- Drawer header --}}
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                    <svg class="h-4.5 w-4.5 text-rose-600" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-[14px] font-bold text-[#17211c]">New Return</h2>
                    <p class="text-[11px] text-slate-400">Log a returned sale item</p>
                </div>
                <button type="button" id="closeReturnDrawer"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Drawer body (scrollable form) --}}
            <form method="post" action="{{ route('returns.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

                    {{-- Completed sale --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Completed Sale <span class="text-rose-400">*</span>
                        </label>
                        <select name="sale_id" required class="ppp-field">
                            <option value="">Select sale…</option>
                            @foreach($sales as $sale)
                                <option value="{{ $sale->id }}">
                                    {{ $sale->sold_date->format('d M') }} · {{ $sale->product_name }} · Qty {{ $sale->quantity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Return quantity --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Return Quantity <span class="text-rose-400">*</span>
                        </label>
                        <input name="quantity" type="number" min="1" value="1" required class="ppp-field">
                    </div>

                    {{-- Condition toggle --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Condition <span class="text-rose-400">*</span>
                        </label>
                        <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="condition" value="good" class="sr-only" checked>
                                <span class="cond-good flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-[12px]
                                             font-semibold transition-all duration-200 bg-emerald-500 text-white shadow-sm">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Good
                                </span>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="condition" value="damaged" class="sr-only">
                                <span class="cond-damaged flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-[12px]
                                             font-semibold transition-all duration-200 text-slate-500 hover:text-slate-700">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Damaged
                                </span>
                            </label>
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-400" id="condHint">
                            Good items are added back to stock.
                        </p>
                    </div>

                    {{-- Return date --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Return Date <span class="text-rose-400">*</span>
                        </label>
                        <input name="return_date" type="date" value="{{ now()->toDateString() }}" required class="ppp-field">
                    </div>

                    {{-- Reason --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Reason
                        </label>
                        <textarea name="reason" rows="3" placeholder="Why was this returned?" class="ppp-field"></textarea>
                    </div>
                </div>

                {{-- Drawer footer --}}
                <div class="flex gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    <button type="button" id="cancelReturnDrawer"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold
                                   text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5
                                   text-sm font-semibold text-white shadow-sm transition-all duration-200
                                   hover:bg-rose-700 hover:shadow-md active:scale-[0.99]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Return
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        // ── Drawer open/close ──────────────────────────────────────
        const rDrawer   = document.getElementById('returnDrawer');
        const rBackdrop = document.getElementById('returnDrawerBackdrop');
        const rPanel    = document.getElementById('returnDrawerPanel');

        function openReturnDrawer() {
            rDrawer.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                rBackdrop.classList.remove('opacity-0');
                rPanel.classList.remove('translate-x-full');
            });
        }
        function closeReturnDrawer() {
            rBackdrop.classList.add('opacity-0');
            rPanel.classList.add('translate-x-full');
            document.body.style.overflow = '';
            setTimeout(() => rDrawer.classList.add('hidden'), 300);
        }

        document.getElementById('openReturnDrawer').addEventListener('click', openReturnDrawer);
        document.getElementById('closeReturnDrawer').addEventListener('click', closeReturnDrawer);
        document.getElementById('cancelReturnDrawer').addEventListener('click', closeReturnDrawer);
        rBackdrop.addEventListener('click', closeReturnDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !rDrawer.classList.contains('hidden')) closeReturnDrawer();
        });

        // Re-open drawer automatically if there were validation errors
        @if($errors->any())
            openReturnDrawer();
        @endif

        // ── Condition toggle styling + hint ────────────────────────
        $('input[name="condition"]').on('change', function () {
            $('.cond-good, .cond-damaged')
                .removeClass('bg-emerald-500 bg-rose-500 text-white shadow-sm')
                .addClass('text-slate-500');
            if (this.value === 'good') {
                $('.cond-good').removeClass('text-slate-500').addClass('bg-emerald-500 text-white shadow-sm');
                $('#condHint').text('Good items are added back to stock.');
            } else {
                $('.cond-damaged').removeClass('text-slate-500').addClass('bg-rose-500 text-white shadow-sm');
                $('#condHint').text('Damaged items are recorded as loss (not restocked).');
            }
        });

        // ── Client-side search ─────────────────────────────────────
        $('#returnSearch').on('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            $('#returnTableBody tr.tbl-row').each(function () {
                const match = $(this).data('search').indexOf(q) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#returnNoResults').toggleClass('hidden', visible !== 0 || q === '');
        });
    </script>
    @endpush
</x-app-layout>
