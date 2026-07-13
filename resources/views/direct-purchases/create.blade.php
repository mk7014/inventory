<x-app-layout title="New Direct Purchase">
    @include('partials.page-header', [
        'title'    => 'New Direct Purchase',
        'subtitle' => 'Buy stock directly from a supplier — no requisition required',
    ])

    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    <form method="post" action="{{ route('direct-purchases.store') }}" id="dpForm"
          class="space-y-6 rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
        @csrf

        {{-- ── Header fields ─────────────────────────────────────── --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    Purchase Date <span class="text-red-400">*</span>
                </label>
                <input name="purchase_date" type="date" value="{{ old('purchase_date', now()->toDateString()) }}" required
                       class="ppp-field">
            </div>

            @if($isAdmin)
            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    Employee <span class="text-red-400">*</span>
                </label>
                <select name="employee_id" id="employeeSelect" required
                        class="ppp-field">
                    <option value="">Select employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" data-balance="{{ $emp->balance }}" @selected(old('employee_id') == $emp->id)>{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Supplier</label>
                <select name="supplier_id"
                        class="ppp-field">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Warehouse</label>
                <select name="warehouse_id"
                        class="ppp-field">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Invoice Number</label>
                <input name="invoice_number" value="{{ old('invoice_number') }}" placeholder="Optional"
                       class="ppp-field">
            </div>

            <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference Number</label>
                <input name="reference_number" value="{{ old('reference_number') }}" placeholder="Optional"
                       class="ppp-field">
            </div>

            <div class="sm:col-span-2 lg:col-span-1">
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Balance After Purchase</label>
                <div id="balanceDisplay"
                     class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-3 py-2.5 text-sm font-bold text-emerald-700">
                    ৳ {{ number_format($isAdmin ? 0 : (float) auth()->user()->balance, 2) }}
                </div>
                <p id="balanceHint" class="mt-1 text-[11px] text-slate-400">
                    Deducted on approval. A negative balance is what the company owes back.
                </p>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Remarks</label>
            <textarea name="remarks" rows="2" placeholder="Optional notes"
                      class="ppp-field">{{ old('remarks') }}</textarea>
        </div>

        {{-- ── Product rows ──────────────────────────────────────── --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Products</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Items received into stock on approval</p>
                </div>
                <button type="button" id="addProductRow"
                        class="rounded-lg border border-[#287857] px-3 py-1.5 text-[12px] font-semibold
                               text-[#287857] transition-all duration-200 hover:bg-[#287857] hover:text-white">
                    + Add Product
                </button>
            </div>
            <div class="overflow-x-auto rounded-xl border border-slate-200/60">
                <table class="w-full min-w-240 text-left text-sm" id="productTable">
                    <thead class="bg-slate-50/80">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-3 py-3">Product</th>
                            <th class="px-3 py-3">SKU</th>
                            <th class="px-3 py-3 w-20">Qty</th>
                            <th class="px-3 py-3 w-24">Unit</th>
                            <th class="px-3 py-3 w-28">Unit Price</th>
                            <th class="px-3 py-3 w-24">Discount</th>
                            <th class="px-3 py-3 w-24">Tax</th>
                            <th class="px-3 py-3 w-28">Line Total</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody" class="divide-y divide-slate-100"></tbody>
                </table>
                <div id="productEmptyMsg" class="px-5 py-4 text-[12px] text-slate-400">
                    No products added. Click "+ Add Product" to add one.
                </div>
            </div>
        </div>

        {{-- ── Summary ───────────────────────────────────────────── --}}
        <div class="flex flex-col items-end gap-2 border-t border-slate-100 pt-5">
            <div class="w-full max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between text-slate-500"><span>Subtotal</span><span id="sumSubtotal">৳ 0.00</span></div>
                <div class="flex justify-between text-slate-500"><span>Discount</span><span id="sumDiscount">৳ 0.00</span></div>
                <div class="flex justify-between text-slate-500"><span>Tax</span><span id="sumTax">৳ 0.00</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1.5 text-base font-bold text-[#17211c]">
                    <span>Grand Total</span><span id="sumGrand" class="text-[#287857]">৳ 0.00</span>
                </div>
            </div>
            <button type="submit"
                    class="mt-3 rounded-xl bg-[#287857] px-6 py-2.5 text-sm font-semibold text-white
                           shadow-sm transition-all duration-200 hover:bg-[#1f6046] hover:shadow-md">
                Submit Direct Purchase
            </button>
        </div>
    </form>

    @php
        $productsData = $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->current_stock, 'price' => $p->default_purchase_price]);
    @endphp

    @push('scripts')
    <script>
        let rowIndex = 0;
        const isAdmin = @json($isAdmin);
        const products = @json($productsData);
        const money = v => '৳ ' + Number(v || 0).toFixed(2);

        function buildProductOptions() {
            return products.map(p =>
                `<option value="${p.id}" data-sku="${p.sku || ''}" data-price="${p.price || ''}" data-stock="${p.stock}">${p.name}</option>`
            ).join('');
        }

        function currentBalance() {
            if (!isAdmin) return Number(@json((float) auth()->user()->balance));
            const opt = $('#employeeSelect option:selected');
            return Number(opt.data('balance') || 0);
        }

        // Shows where the wallet lands once this purchase is approved. Going
        // negative is allowed — it just means the company owes that much back.
        function renderBalance(grand = 0) {
            const after = currentBalance() - grand;
            $('#balanceDisplay')
                .text(money(after))
                .toggleClass('border-emerald-100 bg-emerald-50/60 text-emerald-700', after >= 0)
                .toggleClass('border-red-100 bg-red-50/60 text-red-600', after < 0);
        }

        function recalc() {
            let subtotal = 0, discount = 0, tax = 0, grand = 0;

            $('#productTableBody .product-row').each(function () {
                const qty   = Number($(this).find('.qty').val() || 0);
                const price = Number($(this).find('.price').val() || 0);
                const disc  = Number($(this).find('.discount').val() || 0);
                const tx    = Number($(this).find('.tax').val() || 0);
                const base  = qty * price;
                const line  = base - disc + tx;

                subtotal += base;
                discount += disc;
                tax      += tx;
                grand    += line;

                $(this).find('.line-total').text(money(line));
            });

            $('#sumSubtotal').text(money(subtotal));
            $('#sumDiscount').text(money(discount));
            $('#sumTax').text(money(tax));
            $('#sumGrand').text(money(grand));

            renderBalance(grand);
        }

        function updateEmptyMsg() {
            $('#productEmptyMsg').toggle($('#productTableBody .product-row').length === 0);
        }

        function addProductRow() {
            const idx = rowIndex++;
            const tr = $(`
                <tr class="product-row">
                    <td class="px-3 py-2.5">
                        <select name="items[${idx}][product_id]"
                                class="product-select w-44 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                            <option value="">Select product</option>
                            ${buildProductOptions()}
                        </select>
                        <div class="stock-hint mt-1 text-[11px] text-slate-400"></div>
                    </td>
                    <td class="px-3 py-2.5">
                        <input class="sku w-24 rounded-lg border border-slate-200 bg-slate-50 px-2 py-2 text-sm text-slate-500" readonly>
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][quantity]" type="number" min="1" value="1"
                               class="qty w-16 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][unit]" placeholder="pc"
                               class="w-20 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none">
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][purchase_price]" type="number" min="0.01" step="0.01"
                               class="price w-24 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none" required>
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][discount]" type="number" min="0" step="0.01" value="0"
                               class="discount w-20 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none">
                    </td>
                    <td class="px-3 py-2.5">
                        <input name="items[${idx}][tax]" type="number" min="0" step="0.01" value="0"
                               class="tax w-20 rounded-lg border border-slate-200 px-2 py-2 text-sm focus:border-[#287857] focus:outline-none">
                    </td>
                    <td class="line-total px-3 py-2.5 text-sm font-semibold text-slate-700">৳ 0.00</td>
                    <td class="px-3 py-2.5">
                        <button type="button"
                                class="remove-row rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50">
                            Remove
                        </button>
                    </td>
                </tr>
            `);
            $('#productTableBody').append(tr);
            updateEmptyMsg();
        }

        $('#addProductRow').on('click', addProductRow);

        $('#productTableBody').on('input change', '.qty,.price,.discount,.tax,.product-select', function () {
            const row = $(this).closest('.product-row');
            if ($(this).hasClass('product-select')) {
                const opt = row.find('.product-select option:selected');
                row.find('.sku').val(opt.data('sku') || '');
                row.find('.stock-hint').text(opt.val() ? `Current stock: ${opt.data('stock') || 0}` : '');
                if (opt.data('price')) row.find('.price').val(opt.data('price'));
            }
            recalc();
        });

        $('#productTableBody').on('click', '.remove-row', function () {
            $(this).closest('.product-row').remove();
            updateEmptyMsg();
            recalc();
        });

        $('#employeeSelect').on('change', recalc);

        $('#dpForm').on('submit', function (e) {
            if ($('#productTableBody .product-row').length === 0) {
                e.preventDefault();
                alert('Please add at least one product before submitting.');
            }
        });

        // ── Init ──
        addProductRow();
        recalc();
    </script>
    @endpush
</x-app-layout>
