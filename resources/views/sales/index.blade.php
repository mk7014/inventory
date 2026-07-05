<x-app-layout title="Sales">
    @include('partials.page-header', [
        'title'    => 'Sales',
        'subtitle' => 'Record completed Daraz sales from stock or new purchase',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── New Sale Form ──────────────────────────────────────── --}}
        <div class="xl:col-span-1">
            <form method="post" action="{{ route('sales.store') }}" id="saleForm"
                  class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                @csrf

                {{-- Form header --}}
                <div class="border-b border-slate-100 px-5 py-4 flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl
                                bg-emerald-50">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[13px] font-bold text-[#17211c]">New Sale</h2>
                        <p class="text-[11px] text-slate-400">Fill in the details below</p>
                    </div>
                </div>

                <div class="space-y-4 p-5">

                    {{-- Daraz Account --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Daraz Account
                        </label>
                        <select name="daraz_account_id"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                       text-slate-700 transition focus:border-[#287857] focus:outline-none" required>
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
                        <div class="flex rounded-xl border border-slate-200 overflow-hidden">
                            <label class="source-tab flex-1 cursor-pointer">
                                <input type="radio" name="source" value="new_purchase" class="sr-only" checked>
                                <span class="flex items-center justify-center gap-1.5 px-3 py-2.5 text-[12px] font-semibold
                                             transition-all duration-200 bg-amber-500 text-white">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    New Purchase
                                </span>
                            </label>
                            <label class="source-tab flex-1 cursor-pointer border-l border-slate-200">
                                <input type="radio" name="source" value="stock" id="saleSource" class="sr-only">
                                <span class="flex items-center justify-center gap-1.5 px-3 py-2.5 text-[12px] font-semibold
                                             transition-all duration-200 text-slate-500 hover:bg-slate-50">
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
                        <select name="product_id" id="saleProduct"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                       text-slate-700 transition focus:border-[#287857] focus:outline-none">
                            <option value="">Select product…</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <div id="stockResult" class="mt-1.5 hidden items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5">
                            <svg class="h-3.5 w-3.5 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
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
                        <input name="product_name"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 placeholder-slate-300 transition
                                      focus:border-[#287857] focus:outline-none"
                               placeholder="Enter manually…">
                    </div>

                    {{-- Qty + Price row --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Quantity
                            </label>
                            <input name="quantity" id="saleQty" type="number" min="1" value="1"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                          text-slate-700 transition focus:border-[#287857] focus:outline-none"
                                   required>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Unit Price (৳)
                            </label>
                            <input name="selling_price" id="salePrice" type="number" min="0.01" step="0.01"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                          text-slate-700 placeholder-slate-300 transition
                                          focus:border-[#287857] focus:outline-none"
                                   placeholder="0.00" required>
                        </div>
                    </div>

                    {{-- Revenue preview --}}
                    <div id="revenuePreview"
                         class="hidden items-center justify-between rounded-xl bg-emerald-50
                                border border-emerald-100 px-4 py-3">
                        <span class="text-[11px] font-semibold text-emerald-700">Estimated Revenue</span>
                        <span id="revenueAmt" class="text-base font-bold text-emerald-700">৳ 0.00</span>
                    </div>

                    {{-- Sale date --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Sale Date
                        </label>
                        <input name="sold_date" type="date" value="{{ now()->toDateString() }}"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 transition focus:border-[#287857] focus:outline-none"
                               required>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                            class="w-full rounded-xl bg-[#287857] px-4 py-3 text-sm font-semibold text-white
                                   shadow-sm transition-all duration-200 hover:bg-[#1f6046] hover:shadow-md
                                   flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Sale
                    </button>

                </div>
            </form>
        </div>

        {{-- ── Sales Table ────────────────────────────────────────── --}}
        <section class="xl:col-span-2 flex flex-col rounded-2xl border border-slate-200/60
                        bg-white shadow-sm overflow-hidden">

            {{-- Table header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Sales History</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $sales->total() }} total records</p>
                </div>
                @php
                    $pageRevenue = $sales->getCollection()->sum(fn($s) => $s->quantity * $s->selling_price);
                @endphp
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-3 py-2 text-right">
                    <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wider">Page Revenue</p>
                    <p class="text-sm font-bold text-emerald-700">৳ {{ number_format($pageRevenue, 2) }}</p>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto flex-1">
                <table class="w-full min-w-160 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Qty</th>
                            <th class="px-5 py-3">Revenue</th>
                            <th class="px-5 py-3">Source</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sales as $sale)
                            <tr class="tbl-row">
                                <td class="px-5 py-3 text-xs text-slate-500">
                                    {{ $sale->sold_date->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="text-[12px] font-medium text-slate-700">
                                        {{ $sale->account->account_name }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 max-w-45">
                                    <span class="block truncate text-[12px] text-slate-700"
                                          title="{{ $sale->product_name }}">
                                        {{ $sale->product_name }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-[12px] font-medium text-slate-600">
                                    {{ $sale->quantity }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="font-semibold text-emerald-700">
                                        ৳ {{ number_format($sale->quantity * $sale->selling_price, 2) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($sale->source === 'stock')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50
                                                     px-2.5 py-1 text-[10px] font-semibold text-indigo-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                                            Stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50
                                                     px-2.5 py-1 text-[10px] font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            New Purchase
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @include('partials.status', ['status' => $sale->status])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <span class="text-sm text-slate-400">No sales found.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sales->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $sales->links() }}
            </div>
            @endif

        </section>
    </div>

    @push('scripts')
    <script>
        // Source radio toggle styling
        $('input[name="source"]').on('change', function () {
            $('.source-tab span').each(function () {
                $(this).removeClass('bg-amber-500 text-white bg-indigo-500')
                       .addClass('text-slate-500');
            });
            const span = $(this).closest('label').find('span');
            const isStock = $(this).val() === 'stock';
            span.removeClass('text-slate-500')
                .addClass(isStock ? 'bg-indigo-500 text-white' : 'bg-amber-500 text-white');
        });

        // Stock check
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

        // Revenue preview
        function updateRevenue() {
            const qty   = Number($('#saleQty').val() || 0);
            const price = Number($('#salePrice').val() || 0);
            const rev   = qty * price;
            if (rev > 0) {
                $('#revenueAmt').text('৳ ' + rev.toFixed(2));
                $('#revenuePreview').removeClass('hidden').addClass('flex');
            } else {
                $('#revenuePreview').addClass('hidden').removeClass('flex');
            }
        }

        $('#saleQty, #salePrice').on('input', updateRevenue);
    </script>
    @endpush
</x-app-layout>
