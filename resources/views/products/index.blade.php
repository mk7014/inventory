<x-app-layout title="Products">
    @include('partials.page-header', [
        'title'    => 'Products & Stock',
        'subtitle' => 'Master product list and current stock balance',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Add Product Form --}}
        <div class="xl:col-span-1">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[13px] font-bold text-[#17211c]">Add Product</h2>
                        <p class="text-[11px] text-slate-400">New product to master list</p>
                    </div>
                </div>
                <form method="post" action="{{ route('products.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Product Name <span class="text-red-400">*</span>
                        </label>
                        <input name="name" required placeholder="e.g. Phone Charger 20W"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 placeholder-slate-300 focus:border-indigo-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            SKU
                        </label>
                        <input name="sku" placeholder="e.g. CHG-20W-001"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 placeholder-slate-300 focus:border-indigo-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Default Purchase Price (৳)
                        </label>
                        <input name="default_purchase_price" type="number" step="0.01" min="0"
                               placeholder="0.00"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 placeholder-slate-300 focus:border-indigo-400 focus:outline-none">
                    </div>
                    <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white
                                   shadow-sm transition hover:bg-indigo-700 flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Product
                    </button>
                </form>
            </div>
        </div>

        {{-- Products Table --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Product List</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $products->total() }} products</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-180 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">SKU</th>
                            <th class="px-5 py-3">Default Cost</th>
                            <th class="px-5 py-3">Stock</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($products as $product)
                            <tr class="tbl-row align-middle">
                                <form method="post" action="{{ route('products.update', $product) }}">
                                    @csrf @method('put')
                                    <td class="px-4 py-2.5">
                                        <input name="name" value="{{ $product->name }}"
                                               class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm
                                                      text-slate-700 focus:border-indigo-400 focus:outline-none">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="sku" value="{{ $product->sku }}"
                                               class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm
                                                      text-slate-500 focus:border-indigo-400 focus:outline-none">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="default_purchase_price" type="number" step="0.01"
                                               value="{{ $product->default_purchase_price }}"
                                               class="w-28 rounded-lg border border-slate-200 px-2.5 py-2 text-sm
                                                      text-slate-700 focus:border-indigo-400 focus:outline-none">
                                    </td>
                                    <td class="px-5 py-2.5">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold
                                                     {{ $product->current_stock <= 5 ? 'bg-rose-50 text-rose-700' : ($product->current_stock <= 20 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}">
                                            <span class="h-1.5 w-1.5 rounded-full
                                                         {{ $product->current_stock <= 5 ? 'bg-rose-400' : ($product->current_stock <= 20 ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
                                            {{ $product->current_stock }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <button class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5
                                                       text-[11px] font-semibold text-indigo-700 transition
                                                       hover:bg-indigo-600 hover:text-white hover:border-transparent">
                                            Save
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $products->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
