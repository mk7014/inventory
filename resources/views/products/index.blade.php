<x-app-layout title="Products">

    {{-- ── Page header with Add Product trigger ───────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Products &amp; Stock</h1>
            <p class="mt-1 text-sm text-[#617068]">Master product list and current stock balance</p>
        </div>
        <button type="button" id="openAddProduct"
                class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm
                       font-semibold text-white shadow-sm ring-1 ring-indigo-900/5 transition-all
                       duration-200 hover:bg-indigo-700 hover:shadow-md active:scale-[0.98]">
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </button>
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#4f46e5 0%,rgba(79,70,229,0.15) 40%,transparent 100%);"></div>

    {{-- ── KPI stat cards ─────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">

        {{-- Inventory value (feature card) --}}
        <div class="relative overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-indigo-900/10"
             style="background: linear-gradient(135deg,#4f46e5 0%,#3730a3 100%);">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-50/80">Inventory Value</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">৳ {{ number_format($stats['inv_value'], 0) }}</p>
                <p class="mt-1 text-[11px] font-medium text-indigo-50/70">Cost × stock on hand</p>
            </div>
        </div>

        {{-- Total products --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Products</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['total']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">In master list</p>
        </div>

        {{-- Units in stock --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Units in Stock</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-700">{{ number_format($stats['units']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Total quantity</p>
        </div>

        {{-- Low stock --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Low Stock</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight {{ $stats['low_stock'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                {{ number_format($stats['low_stock']) }}
            </p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">≤ 5 units left</p>
        </div>
    </div>

    {{-- ── Product table (full width) ─────────────────────────────── --}}
    <section class="flex flex-col rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">

        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Product List</h2>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $products->total() }} products</p>
            </div>
            <div class="relative w-full sm:w-64">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-300"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="productSearch" type="text" placeholder="Search name or SKU…"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-9 pr-3 text-[13px]
                              text-slate-700 placeholder-slate-300 transition
                              focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/10">
            </div>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="w-full min-w-180 text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Image</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">SKU</th>
                        <th class="px-5 py-3 text-right">Default Cost</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="productTableBody" class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="tbl-row align-middle"
                            data-search="{{ strtolower($product->name . ' ' . $product->sku) }}">

                            {{-- Image thumbnail --}}
                            <td class="px-5 py-3">
                                <div class="h-12 w-12 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    @if($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-300">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <span class="text-[13px] font-semibold text-slate-800">{{ $product->name }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($product->sku)
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-500">{{ $product->sku }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-[13px] text-slate-600">
                                {{ $product->default_purchase_price !== null ? '৳ '.number_format($product->default_purchase_price, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold
                                             {{ $product->current_stock <= 5 ? 'bg-rose-50 text-rose-700' : ($product->current_stock <= 20 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">
                                    <span class="h-1.5 w-1.5 rounded-full
                                                 {{ $product->current_stock <= 5 ? 'bg-rose-400' : ($product->current_stock <= 20 ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
                                    {{ $product->current_stock }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button type="button"
                                        class="edit-product inline-flex items-center gap-1.5 rounded-lg border border-slate-200
                                               bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition
                                               hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                                        data-action="{{ route('products.update', $product) }}"
                                        data-name="{{ $product->name }}"
                                        data-sku="{{ $product->sku }}"
                                        data-price="{{ $product->default_purchase_price }}"
                                        data-image="{{ $product->imageUrl() }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50">
                                        <svg class="h-7 w-7 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">No products yet</p>
                                        <p class="mt-0.5 text-[12px] text-slate-400">Click “Add Product” to create your first one.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="productNoResults" class="hidden">
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No products match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $products->links() }}</div>
        @endif
    </section>

    {{-- ── Slide-over drawer: Add / Edit Product ──────────────────── --}}
    <div id="productDrawer" class="fixed inset-0 z-40 hidden">
        <div id="productDrawerBackdrop"
             class="absolute inset-0 bg-slate-900/40 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>

        <div id="productDrawerPanel"
             class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-white
                    shadow-2xl transition-transform duration-300 ease-in-out">

            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50">
                    <svg id="drawerIcon" class="h-4.5 w-4.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 id="drawerTitle" class="text-[14px] font-bold text-[#17211c]">Add Product</h2>
                    <p id="drawerSubtitle" class="text-[11px] text-slate-400">New product to master list</p>
                </div>
                <button type="button" id="closeProductDrawer"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="post" id="productForm" action="{{ route('products.store') }}" enctype="multipart/form-data"
                  class="flex min-h-0 flex-1 flex-col">
                @csrf
                {{-- Method spoofing: disabled for Add (POST), enabled=PUT for Edit --}}
                <input type="hidden" name="_method" id="formMethod" value="PUT" disabled>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">

                    {{-- Image upload with preview --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Product Image
                        </label>
                        <label class="group relative flex h-36 cursor-pointer flex-col items-center justify-center gap-2
                                      overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-slate-50
                                      transition hover:border-indigo-300 hover:bg-indigo-50/40">
                            <img id="fThumb" class="absolute inset-0 hidden h-full w-full object-contain" alt="">
                            <div id="fPh" class="flex flex-col items-center gap-2 text-slate-400">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-[12px] font-medium">Click to upload</span>
                                <span class="text-[10px] text-slate-300">PNG, JPG or WEBP · max 2MB</span>
                            </div>
                            <input type="file" name="image" id="fImage" accept="image/*" class="hidden">
                        </label>
                        <p id="fImageHint" class="mt-1.5 hidden text-[11px] text-slate-400">
                            Leave empty to keep the current image.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Product Name <span class="text-rose-400">*</span>
                        </label>
                        <input name="name" id="fName" required placeholder="e.g. Phone Charger 20W" class="ppp-field">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">SKU</label>
                        <input name="sku" id="fSku" placeholder="e.g. CHG-20W-001" class="ppp-field">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Default Purchase Price (৳)
                        </label>
                        <input name="default_purchase_price" id="fPrice" type="number" step="0.01" min="0" placeholder="0.00" class="ppp-field">
                    </div>
                </div>

                <div class="flex gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    <button type="button" id="cancelProductDrawer"
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
                        <span id="submitLabel">Save Product</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const storeUrl = '{{ route('products.store') }}';

        // ── Drawer open/close ──────────────────────────────────────
        const pDrawer   = document.getElementById('productDrawer');
        const pBackdrop = document.getElementById('productDrawerBackdrop');
        const pPanel    = document.getElementById('productDrawerPanel');
        const form      = document.getElementById('productForm');
        const fMethod   = document.getElementById('formMethod');
        const fThumb    = document.getElementById('fThumb');
        const fPh       = document.getElementById('fPh');
        const fImage    = document.getElementById('fImage');
        const fImageHint= document.getElementById('fImageHint');

        function showDrawer() {
            pDrawer.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                pBackdrop.classList.remove('opacity-0');
                pPanel.classList.remove('translate-x-full');
            });
        }
        function closeProductDrawer() {
            pBackdrop.classList.add('opacity-0');
            pPanel.classList.add('translate-x-full');
            document.body.style.overflow = '';
            setTimeout(() => pDrawer.classList.add('hidden'), 300);
        }

        function setPreview(url) {
            if (url) {
                fThumb.src = url;
                fThumb.classList.remove('hidden');
                fPh.classList.add('hidden');
            } else {
                fThumb.removeAttribute('src');
                fThumb.classList.add('hidden');
                fPh.classList.remove('hidden');
            }
        }

        // Open in ADD mode
        function openAddProduct() {
            form.action = storeUrl;
            fMethod.disabled = true;                 // POST → store
            form.reset();
            fImage.value = '';
            setPreview(null);
            fImageHint.classList.add('hidden');
            document.getElementById('drawerTitle').textContent = 'Add Product';
            document.getElementById('drawerSubtitle').textContent = 'New product to master list';
            document.getElementById('submitLabel').textContent = 'Save Product';
            showDrawer();
            document.getElementById('fName').focus();
        }

        // Open in EDIT mode from a row button
        function openEditProduct(btn) {
            form.action = btn.dataset.action;
            fMethod.disabled = false;                // POST + _method=PUT → update
            fImage.value = '';
            document.getElementById('fName').value  = btn.dataset.name || '';
            document.getElementById('fSku').value   = btn.dataset.sku || '';
            document.getElementById('fPrice').value = btn.dataset.price || '';
            setPreview(btn.dataset.image || null);
            fImageHint.classList.remove('hidden');   // "leave empty to keep current image"
            document.getElementById('drawerTitle').textContent = 'Edit Product';
            document.getElementById('drawerSubtitle').textContent = btn.dataset.name || 'Update product details';
            document.getElementById('submitLabel').textContent = 'Update Product';
            showDrawer();
            document.getElementById('fName').focus();
        }

        document.getElementById('openAddProduct').addEventListener('click', openAddProduct);
        document.querySelectorAll('.edit-product').forEach(function (btn) {
            btn.addEventListener('click', function () { openEditProduct(btn); });
        });

        document.getElementById('closeProductDrawer').addEventListener('click', closeProductDrawer);
        document.getElementById('cancelProductDrawer').addEventListener('click', closeProductDrawer);
        pBackdrop.addEventListener('click', closeProductDrawer);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !pDrawer.classList.contains('hidden')) closeProductDrawer();
        });

        // Re-open in ADD mode if a create/update validation error occurred
        @if($errors->any())
            openAddProduct();
        @endif

        // ── Image preview on file pick ─────────────────────────────
        fImage.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) setPreview(URL.createObjectURL(file));
        });

        // ── Client-side search ─────────────────────────────────────
        $('#productSearch').on('input', function () {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            $('#productTableBody tr[data-search]').each(function () {
                const match = $(this).data('search').indexOf(q) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#productNoResults').toggleClass('hidden', visible !== 0 || q === '');
        });
    </script>
    @endpush
</x-app-layout>
