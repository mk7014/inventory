<x-app-layout title="Suppliers">
    @include('partials.page-header', [
        'title'    => 'Suppliers',
        'subtitle' => 'Vendors you buy stock from via direct purchase',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Add Supplier Form --}}
        <div class="xl:col-span-1">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[13px] font-bold text-[#17211c]">Add Supplier</h2>
                        <p class="text-[11px] text-slate-400">Register a new vendor</p>
                    </div>
                </div>
                <form method="post" action="{{ route('suppliers.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Name <span class="text-red-400">*</span>
                        </label>
                        <input name="name" required value="{{ old('name') }}" placeholder="e.g. Dhaka Wholesale"
                               class="ppp-field">
                        @error('name')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Phone</label>
                        <input name="phone" value="{{ old('phone') }}" placeholder="Optional"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Email</label>
                        <input name="email" type="email" value="{{ old('email') }}" placeholder="Optional"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Address</label>
                        <input name="address" value="{{ old('address') }}" placeholder="Optional"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
                        <select name="status"
                                class="ppp-field">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="w-full rounded-xl bg-[#287857] px-4 py-3 text-sm font-semibold text-white
                                   shadow-sm transition hover:bg-[#1f6046] flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Supplier
                    </button>
                </form>
            </div>
        </div>

        {{-- Suppliers Table --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">All Suppliers</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $suppliers->total() }} suppliers</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-200 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Address</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($suppliers as $supplier)
                            <tr class="tbl-row align-middle">
                                <form method="post" action="{{ route('suppliers.update', $supplier) }}">
                                    @csrf @method('put')
                                    <td class="px-4 py-2.5">
                                        <input name="name" value="{{ $supplier->name }}"
                                               class="ppp-field">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="phone" value="{{ $supplier->phone }}"
                                               class="w-32 rounded-lg border border-slate-200 px-2.5 py-2 text-sm
                                                      text-slate-600 focus:border-[#287857] focus:outline-none">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="address" value="{{ $supplier->address }}"
                                               class="ppp-field">
                                        <input type="hidden" name="email" value="{{ $supplier->email }}">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select name="status"
                                                class="ppp-field">
                                            <option value="active" @selected($supplier->status === 'active')>Active</option>
                                            <option value="inactive" @selected($supplier->status === 'inactive')>Inactive</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <button class="rounded-lg border border-[#287857]/40 bg-emerald-50 px-3 py-1.5
                                                       text-[11px] font-semibold text-[#287857] transition
                                                       hover:bg-[#287857] hover:text-white hover:border-transparent">
                                            Save
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No suppliers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($suppliers->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $suppliers->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
