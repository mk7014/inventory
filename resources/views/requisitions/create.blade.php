<x-app-layout title="New Requisition">
    @include('partials.page-header', ['title' => 'New Requisition', 'subtitle' => 'Add product items and/or other costs in a single request'])

    <form method="post" action="{{ route('requisitions.store') }}" id="requisitionForm"
          class="space-y-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
        @csrf

        {{-- ── Section 1: Product Items (optional) ──────────────── --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Product Items
                        <span class="ml-1 text-[10px] font-normal text-slate-400">(optional)</span>
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Daraz order product purchases</p>
                </div>
                <button type="button" id="addProductRow"
                        class="rounded-lg border border-[#287857] px-3 py-1.5 text-[12px] font-semibold
                               text-[#287857] transition-all duration-200 hover:bg-[#287857] hover:text-white">
                    + Add Product
                </button>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200/60">
                <table class="w-full min-w-215 text-left text-sm" id="productTable">
                    <thead class="bg-slate-50/80">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Account</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Daraz Order ID</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit Cost</th>
                            <th class="px-4 py-3">Subtotal</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody" class="divide-y divide-slate-100"></tbody>
                </table>
                <div id="productEmptyMsg" class="px-5 py-4 text-[12px] text-slate-400">
                    No product items added. Click "+ Add Product" to add one.
                </div>
            </div>
        </div>

        {{-- ── Section 2: Other Costs (optional) ───────────────── --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Other Costs
                        <span class="ml-1 text-[10px] font-normal text-slate-400">(optional)</span>
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Transportation, packaging, or any misc expense</p>
                </div>
                <button type="button" id="addCostRow"
                        class="rounded-lg border border-violet-400 px-3 py-1.5 text-[12px] font-semibold
                               text-violet-600 transition-all duration-200 hover:bg-violet-600 hover:text-white">
                    + Add Cost
                </button>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200/60">
                <table class="w-full text-left text-sm" id="costTable">
                    <thead class="bg-slate-50/80">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 w-40">Amount (৳)</th>
                            <th class="px-4 py-3 w-20"></th>
                        </tr>
                    </thead>
                    <tbody id="costTableBody" class="divide-y divide-slate-100"></tbody>
                </table>
                <div id="costEmptyMsg" class="px-5 py-4 text-[12px] text-slate-400">
                    No other costs added. Click "+ Add Cost" to add one.
                </div>
            </div>
        </div>

        {{-- ── Grand total + Submit ──────────────────────────────── --}}
        <div class="flex flex-col items-end gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
            <div class="text-lg font-bold text-[#17211c]">
                Grand Total: <span id="grandTotal" class="text-[#287857]">৳ 0.00</span>
            </div>
            <button type="submit"
                    class="rounded-xl bg-[#287857] px-6 py-2.5 text-sm font-semibold text-white
                           shadow-sm transition-all duration-200 hover:bg-[#1f6046] hover:shadow-md">
                Submit Requisition
            </button>
        </div>
    </form>

    @php
        $accountsData = $accounts->map(fn($a) => ['id' => $a->id, 'name' => $a->account_name]);
        $productsData = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'stock' => $p->current_stock, 'price' => $p->default_purchase_price]);
    @endphp

    @push('scripts')
    <script>
        // Shared index counter for items[n]
        let rowIndex = 0;

        const money = v => '৳ ' + Number(v || 0).toFixed(2);

        // Accounts and products data from server
        const accounts = @json($accountsData);
        const products = @json($productsData);

        // ── Helpers ───────────────────────────────────────────────

        function recalc() {
            let total = 0;

            $('#productTableBody .product-row').each(function () {
                const sub = Number($(this).find('.qty').val() || 0) * Number($(this).find('.price').val() || 0);
                total += sub;
                $(this).find('.subtotal').text(money(sub));
            });

            $('#costTableBody .cost-row').each(function () {
                total += Number($(this).find('.cost-amount').val() || 0);
            });

            $('#grandTotal').text(money(total));
        }

        function updateEmptyMsgs() {
            $('#productEmptyMsg').toggle($('#productTableBody .product-row').length === 0);
            $('#costEmptyMsg').toggle($('#costTableBody .cost-row').length === 0);
        }

        function buildAccountOptions() {
            return accounts.map(a => `<option value="${a.id}">${a.name}</option>`).join('');
        }

        function buildProductOptions() {
            return products.map(p => `<option value="${p.id}" data-stock="${p.stock}" data-price="${p.price || ''}">${p.name} (Stock: ${p.stock})</option>`).join('');
        }

        // ── Product rows ──────────────────────────────────────────

        function addProductRow() {
            const idx = rowIndex++;
            const tr = $(`
                <tr class="product-row">
                    <input type="hidden" name="items[${idx}][item_type]" value="product">
                    <td class="px-3 py-2.5">
                        <select name="items[${idx}][daraz_account_id]"
                                class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                            <option value="">Select account</option>
                            ${buildAccountOptions()}
                        </select>
                    </td>
                    <td class="px-3 py-2.5">
                        <select name="items[${idx}][product_id]"
                                class="product-select w-full rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                            <option value="">Select product</option>
                            ${buildProductOptions()}
                        </select>
                        <div class="stock-hint mt-1 text-[11px] text-slate-400"></div>
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][order_id_daraz]"
                               class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none"
                               placeholder="Optional">
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][quantity]" type="number" min="1" value="1"
                               class="qty w-20 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][purchase_price]" type="number" min="0.01" step="0.01"
                               class="price w-28 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                    </td>
                    <td class="subtotal px-3 py-2.5 text-sm font-semibold text-slate-700">৳ 0.00</td>
                    <td class="px-3 py-2.5">
                        <button type="button"
                                class="remove-product-row rounded-lg border border-red-200 px-2.5 py-1.5
                                       text-xs font-medium text-red-600 transition-colors hover:bg-red-50">
                            Remove
                        </button>
                    </td>
                </tr>
            `);
            $('#productTableBody').append(tr);
            updateEmptyMsgs();
        }

        $('#addProductRow').on('click', addProductRow);

        $('#productTableBody').on('input change', '.qty,.price,.product-select', function () {
            const row    = $(this).closest('.product-row');
            const option = row.find('.product-select option:selected');
            row.find('.stock-hint').text(option.val() ? `Available: ${option.data('stock') || 0} units` : '');
            if ($(this).hasClass('product-select') && option.data('price')) {
                row.find('.price').val(option.data('price'));
            }
            recalc();
        });

        $('#productTableBody').on('click', '.remove-product-row', function () {
            $(this).closest('.product-row').remove();
            updateEmptyMsgs();
            recalc();
        });

        // ── Cost rows ─────────────────────────────────────────────

        function addCostRow() {
            const idx = rowIndex++;
            const tr = $(`
                <tr class="cost-row">
                    <input type="hidden" name="items[${idx}][item_type]" value="cost">
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][description]" type="text"
                               class="w-full rounded-lg border border-slate-200 px-2 py-2 text-sm
                                      focus:border-violet-400 focus:outline-none"
                               placeholder="e.g. Transportation fee" required>
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][amount]" type="number" min="0.01" step="0.01"
                               class="cost-amount w-full rounded-lg border border-slate-200 px-2 py-2 text-sm
                                      focus:border-violet-400 focus:outline-none" required>
                    </td>
                    <td class="px-3 py-2.5">
                        <button type="button"
                                class="remove-cost-row rounded-lg border border-red-200 px-2.5 py-1.5
                                       text-xs font-medium text-red-600 transition-colors hover:bg-red-50">
                            Remove
                        </button>
                    </td>
                </tr>
            `);
            $('#costTableBody').append(tr);
            updateEmptyMsgs();
        }

        $('#addCostRow').on('click', addCostRow);

        $('#costTableBody').on('input', '.cost-amount', recalc);

        $('#costTableBody').on('click', '.remove-cost-row', function () {
            $(this).closest('.cost-row').remove();
            updateEmptyMsgs();
            recalc();
        });

        // ── Submit guard: at least one item required ──────────────

        $('#requisitionForm').on('submit', function (e) {
            const total = $('#productTableBody .product-row').length + $('#costTableBody .cost-row').length;
            if (total === 0) {
                e.preventDefault();
                alert('Please add at least one product item or other cost before submitting.');
            }
        });

        // ── Init ──────────────────────────────────────────────────
        updateEmptyMsgs();
        recalc();
    </script>
    @endpush
</x-app-layout>
