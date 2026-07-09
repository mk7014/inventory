<x-app-layout title="Direct Purchases">
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    {{-- ── Page header ────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Direct Purchase</h1>
            <p class="mt-1 text-sm text-[#617068]">Buy stock directly from suppliers — advance or on credit</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('direct-purchases.due') }}" class="ppp-btn-ghost">Due Report</a>
            <a href="{{ route('direct-purchase-payments.index') }}" class="ppp-btn-ghost">Payments</a>
            <a href="{{ route('direct-purchases.create') }}"
               class="group inline-flex items-center gap-2 rounded-xl bg-[#287857] px-4 py-2.5 text-sm font-semibold
                      text-white shadow-sm ring-1 ring-emerald-900/5 transition-all duration-200
                      hover:bg-[#1f6046] hover:shadow-md active:scale-[0.98]">
                <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Direct Purchase
            </a>
        </div>
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#287857 0%,rgba(40,120,87,0.15) 40%,transparent 100%);"></div>

    {{-- ── KPI stat cards ─────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">

        {{-- Purchase value (feature card) --}}
        <div class="relative overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-emerald-900/10"
             style="background: linear-gradient(135deg,#287857 0%,#1f6046 100%);">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-50/80">Purchase Value</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">৳ {{ number_format($stats['total_value'], 0) }}</p>
                <p class="mt-1 text-[11px] font-medium text-emerald-50/70">Approved grand total</p>
            </div>
        </div>

        {{-- Total --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['total']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">All purchases</p>
        </div>

        {{-- Pending --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Pending</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-amber-600">{{ number_format($stats['pending']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Awaiting approval</p>
        </div>

        {{-- Outstanding due --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Outstanding Due</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight {{ $stats['total_due'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                ৳ {{ number_format($stats['total_due'], 0) }}
            </p>
            <a href="{{ route('direct-purchases.due') }}" class="mt-1 inline-block text-[11px] font-medium text-rose-500 hover:underline">
                View due report →
            </a>
        </div>
    </div>

    {{-- ── Filter bar ─────────────────────────────────────────────── --}}
    @php $hasFilters = request()->hasAny(['status', 'payment_type', 'employee_id', 'supplier_id', 'from', 'to']); @endphp
    <form method="get"
          class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
        <div class="flex items-center gap-1.5 pb-2 pr-1 text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 14.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-6.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            <span class="text-[11px] font-semibold uppercase tracking-wider">Filters</span>
        </div>
        <div class="flex-1 min-w-32">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
            <select name="status" class="ppp-field">
                <option value="">All status</option>
                @foreach(['pending','approved','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-32">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Type</label>
            <select name="payment_type" class="ppp-field">
                <option value="">All types</option>
                <option value="advance" @selected(request('payment_type') === 'advance')>Advance</option>
                <option value="due" @selected(request('payment_type') === 'due')>Due</option>
            </select>
        </div>
        @if($isAdmin)
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Employee</label>
            <select name="employee_id" class="ppp-field">
                <option value="">All employees</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Supplier</label>
            <select name="supplier_id" class="ppp-field">
                <option value="">All suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-32">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="ppp-field">
        </div>
        <div class="flex-1 min-w-32">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="ppp-field">
        </div>
        <button class="ppp-btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Filter
        </button>
        @if($hasFilters)
            <a href="{{ route('direct-purchases.index') }}" class="ppp-btn-ghost">Reset</a>
        @endif
    </form>

    {{-- ── Table ──────────────────────────────────────────────────── --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">All Direct Purchases</h2>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $purchases->total() }} records</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-240 text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">DP No</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3 text-right">Grand Total</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Payment</th>
                        <th class="px-5 py-3 text-right">Due</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $row)
                        <tr class="tbl-row">
                            <td class="px-5 py-3">
                                <a class="inline-flex items-center gap-1.5 font-semibold text-[#287857] hover:underline underline-offset-2"
                                   href="{{ route('direct-purchases.show', $row) }}">
                                    <span class="rounded-md bg-emerald-50 px-1.5 py-0.5 font-mono text-[11px] text-emerald-700">
                                        {{ $row->purchase_number }}
                                    </span>
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg
                                                 bg-slate-100 text-[10px] font-bold uppercase text-slate-500">
                                        {{ \Illuminate\Support\Str::of($row->employee->name)->substr(0, 2) }}
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-700">{{ $row->employee->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-[12px] text-slate-600">{{ $row->supplier?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $row->payment_type === 'advance' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ ucfirst($row->payment_type) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-slate-800">৳ {{ number_format($row->grand_total, 2) }}</td>
                            <td class="px-5 py-3">@include('partials.status', ['status' => $row->status])</td>
                            <td class="px-5 py-3">
                                @if($row->status === 'approved')
                                    @include('partials.status', ['status' => $row->payment_status])
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right {{ $row->dueAmount() > 0 ? 'font-semibold text-rose-600' : 'text-slate-400' }}">
                                {{ $row->isDue() && $row->status === 'approved' ? '৳ '.number_format($row->dueAmount(), 2) : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-400">{{ $row->purchase_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50">
                                        <svg class="h-7 w-7 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">No direct purchases found</p>
                                        <p class="mt-0.5 text-[12px] text-slate-400">Try adjusting filters or create a new one.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $purchases->links() }}</div>
        @endif
    </section>
</x-app-layout>
