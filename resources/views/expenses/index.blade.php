<x-app-layout title="Expenses">
    @include('partials.page-header', [
        'title'    => 'Expenses',
        'subtitle' => 'Record what you spend — each expense is deducted from your balance',
        'actions'  => '<a href="'.route('expenses.report').'"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                           </svg>
                           Report &amp; Breakdown
                       </a>',
    ])

    {{-- ── Summary tiles ────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Current Balance</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($balance, 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">Available to spend</p>
        </div>
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">This Month</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($monthTotal, 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ now()->format('F Y') }}</p>
        </div>
        <div class="stat-card rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-rose-600">Total Expenses</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($total, 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $isAdmin ? 'All users' : 'You' }}, filtered</p>
        </div>
        <div class="stat-card rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">Records</p>
            <p class="mt-2 text-2xl font-bold text-[#17211c]">{{ number_format($count) }}</p>
            <p class="mt-1 text-xs text-slate-400">Total entries</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── Add Expense ──────────────────────────────────────────── --}}
        <div class="xl:col-span-1 space-y-4">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                        <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[13px] font-bold text-[#17211c]">Add Expense</h2>
                        <p class="text-[11px] text-slate-400">Deducted from your balance</p>
                    </div>
                </div>
                <form method="post" action="{{ route('expenses.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Category <span class="text-red-400">*</span>
                        </label>
                        <select name="category" required
                                class="ppp-field">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Amount (৳) <span class="text-red-400">*</span>
                        </label>
                        <input name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}"
                               placeholder="0.00"
                               class="ppp-field">
                        @error('amount')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Date <span class="text-red-400">*</span>
                        </label>
                        <input name="expense_date" type="date" required value="{{ old('expense_date', now()->toDateString()) }}"
                               max="{{ now()->toDateString() }}"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Description <span class="text-red-400">*</span>
                        </label>
                        <input name="description" required value="{{ old('description') }}" placeholder="e.g. CNG fare to warehouse"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Note</label>
                        <textarea name="note" rows="2" placeholder="Optional details"
                                  class="ppp-field">{{ old('note') }}</textarea>
                    </div>
                    <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Expense
                    </button>
                </form>
            </div>

            {{-- Request funds (reuses the requisition → approve → pay flow) --}}
            @can('requisitions.create')
            <div class="rounded-2xl border border-sky-100 bg-sky-50/60 p-5">
                <p class="text-[12px] font-bold text-sky-800">Running low on balance?</p>
                <p class="mt-1 text-[11px] text-sky-700/80">Raise a requisition for expense money — an admin will approve and pay it into your balance.</p>
                <a href="{{ route('requisitions.create') }}"
                   class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-4 py-2 text-[12px] font-semibold text-white transition hover:bg-sky-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Request Funds
                </a>
            </div>
            @endcan
        </div>

        {{-- ── Expenses list ────────────────────────────────────────── --}}
        <section class="xl:col-span-2 space-y-4">
            {{-- Filters --}}
            <form method="get" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
                @if($isAdmin)
                <div class="min-w-40">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">User</label>
                    <select name="user_id" class="ppp-field">
                        <option value="">All users</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(request('user_id') == $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="min-w-36">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Category</label>
                    <select name="category" class="ppp-field">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-32">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="ppp-field">
                </div>
                <div class="min-w-32">
                    <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="ppp-field">
                </div>
                <button class="rounded-xl bg-slate-800 px-5 py-2.5 text-[12px] font-semibold text-white transition hover:bg-slate-900">Filter</button>
                @if(request()->hasAny(['from','to','category','user_id']))
                    <a href="{{ route('expenses.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-[12px] font-semibold text-slate-500 transition hover:bg-slate-50">Clear</a>
                @endif
            </form>

            {{-- Table --}}
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-[13px] font-bold text-[#17211c]">Expense Records</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $expenses->total() }} record(s)</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-200 text-left text-sm">
                        <thead class="bg-slate-50/70">
                            <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Date</th>
                                @if($isAdmin)<th class="px-5 py-3">User</th>@endif
                                <th class="px-5 py-3">Category</th>
                                <th class="px-5 py-3">Description</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($expenses as $expense)
                                <tr class="tbl-row">
                                    <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
                                    @if($isAdmin)<td class="px-5 py-3 text-slate-600">{{ $expense->user?->name ?? '—' }}</td>@endif
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600">{{ $expense->category }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700">
                                        {{ $expense->description }}
                                        @if($expense->note)<span class="block text-[11px] text-slate-400">{{ $expense->note }}</span>@endif
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-rose-600">− ৳ {{ number_format($expense->amount, 2) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form method="post" action="{{ route('expenses.destroy', $expense) }}"
                                              onsubmit="return confirm('Delete this expense? ৳{{ number_format($expense->amount, 2) }} will be refunded to the balance.');">
                                            @csrf @method('delete')
                                            <button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-600 hover:text-white hover:border-transparent">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isAdmin ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-slate-400">No expenses recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                <div class="border-t border-slate-100 px-5 py-3">{{ $expenses->links() }}</div>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
