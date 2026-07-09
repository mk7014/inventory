<x-app-layout title="Daraz Accounts">
    @include('partials.page-header', [
        'title'    => 'Daraz Accounts',
        'subtitle' => 'Manage seller accounts and shop labels',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Add Account Form --}}
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
                        <h2 class="text-[13px] font-bold text-[#17211c]">Add Account</h2>
                        <p class="text-[11px] text-slate-400">Register a new Daraz seller account</p>
                    </div>
                </div>
                <form method="post" action="{{ route('accounts.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Account Name <span class="text-red-400">*</span>
                        </label>
                        <input name="account_name" required placeholder="e.g. Main Store"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Shop Name <span class="text-red-400">*</span>
                        </label>
                        <input name="shop_name" required placeholder="e.g. Smart IT Shop"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Status
                        </label>
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
                        Save Account
                    </button>
                </form>
            </div>
        </div>

        {{-- Accounts Table --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">All Accounts</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $accounts->total() }} accounts</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Account Name</th>
                            <th class="px-5 py-3">Shop Name</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"></th>
                            @if(auth()->user()?->isAdmin())<th class="px-5 py-3 text-right">Delete</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($accounts as $account)
                            <tr class="tbl-row align-middle">
                                <form method="post" action="{{ route('accounts.update', $account) }}">
                                    @csrf @method('put')
                                    <td class="px-4 py-2.5">
                                        <input name="account_name" value="{{ $account->account_name }}"
                                               class="ppp-field">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="shop_name" value="{{ $account->shop_name }}"
                                               class="ppp-field">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select name="status"
                                                class="ppp-field">
                                            <option value="active" @selected($account->status === 'active')>Active</option>
                                            <option value="inactive" @selected($account->status === 'inactive')>Inactive</option>
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
                                        @include('partials.delete-button', ['action' => route('accounts.destroy', $account), 'label' => $account->account_name])
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $accounts->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
