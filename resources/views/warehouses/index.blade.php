<x-app-layout title="Warehouses">
    @include('partials.page-header', [
        'title'    => 'Warehouses',
        'subtitle' => 'Storage locations used to label direct purchases',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Add Warehouse Form --}}
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
                        <h2 class="text-[13px] font-bold text-[#17211c]">Add Warehouse</h2>
                        <p class="text-[11px] text-slate-400">Register a storage location</p>
                    </div>
                </div>
                <form method="post" action="{{ route('warehouses.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Name <span class="text-red-400">*</span>
                        </label>
                        <input name="name" required value="{{ old('name') }}" placeholder="e.g. Main Warehouse"
                               class="ppp-field">
                        @error('name')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Location</label>
                        <input name="location" value="{{ old('location') }}" placeholder="Optional"
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
                        Save Warehouse
                    </button>
                </form>
            </div>
        </div>

        {{-- Warehouses Table --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">All Warehouses</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $warehouses->total() }} warehouses</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                            @if(auth()->user()?->isAdmin())<th class="px-4 py-3 text-right">Delete</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($warehouses as $warehouse)
                            <tr class="tbl-row align-middle">
                                <form method="post" action="{{ route('warehouses.update', $warehouse) }}">
                                    @csrf @method('put')
                                    <td class="px-4 py-2.5">
                                        <input name="name" value="{{ $warehouse->name }}"
                                               class="ppp-field">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="location" value="{{ $warehouse->location }}"
                                               class="ppp-field">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select name="status"
                                                class="ppp-field">
                                            <option value="active" @selected($warehouse->status === 'active')>Active</option>
                                            <option value="inactive" @selected($warehouse->status === 'inactive')>Inactive</option>
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
                                @if(auth()->user()?->isAdmin())
                                    <td class="px-4 py-2.5 text-right">
                                        @include('partials.delete-button', ['action' => route('warehouses.destroy', $warehouse), 'label' => $warehouse->name])
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No warehouses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($warehouses->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $warehouses->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
