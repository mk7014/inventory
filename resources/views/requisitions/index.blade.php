<x-app-layout title="Requisitions">

    {{-- ── Page header ────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight text-[#17211c]">Requisitions</h1>
            <p class="mt-1 text-sm text-[#617068]">Request, approve, hold, reject, and pay product purchase funds</p>
        </div>
        <a href="{{ route('requisitions.create') }}"
           class="group inline-flex items-center gap-2 rounded-xl bg-[#287857] px-4 py-2.5 text-sm font-semibold
                  text-white shadow-sm ring-1 ring-emerald-900/5 transition-all duration-200
                  hover:bg-[#1f6046] hover:shadow-md active:scale-[0.98]">
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Requisition
        </a>
    </div>
    <div class="mb-6 h-px w-full rounded-full"
         style="background: linear-gradient(90deg,#287857 0%,rgba(40,120,87,0.15) 40%,transparent 100%);"></div>

    {{-- ── KPI stat cards ─────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">

        {{-- Approved value (feature card) --}}
        <div class="relative overflow-hidden rounded-2xl p-4 text-white shadow-sm ring-1 ring-emerald-900/10"
             style="background: linear-gradient(135deg,#287857 0%,#1f6046 100%);">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-50/80">Approved Value</p>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight">৳ {{ number_format($stats['approved_amount'], 0) }}</p>
                <p class="mt-1 text-[11px] font-medium text-emerald-50/70">Total funds approved</p>
            </div>
        </div>

        {{-- Total --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-[#17211c]">{{ number_format($stats['total']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">All requisitions</p>
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
            <p class="mt-1 text-[11px] font-medium text-slate-400">Awaiting review</p>
        </div>

        {{-- Approved count --}}
        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Approved</p>
            </div>
            <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-700">{{ number_format($stats['approved']) }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">Ready to purchase</p>
        </div>
    </div>

    {{-- ── Filter bar ─────────────────────────────────────────────── --}}
    @php $hasFilters = request()->hasAny(['status', 'from', 'to']); @endphp
    <form method="get"
          class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
        <div class="flex items-center gap-1.5 pb-2 pr-1 text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 14.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-6.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            <span class="text-[11px] font-semibold uppercase tracking-wider">Filters</span>
        </div>
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
            <select name="status" class="ppp-field">
                <option value="">All status</option>
                @foreach(['pending','approved','rejected','hold'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="ppp-field">
        </div>
        <div class="flex-1 min-w-36">
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
            <a href="{{ route('requisitions.index') }}" class="ppp-btn-ghost">Reset</a>
        @endif
    </form>

    {{-- ── Table ──────────────────────────────────────────────────── --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">All Requisitions</h2>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $requisitions->total() }} records</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Req No</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3 text-right">Requested</th>
                        <th class="px-5 py-3 text-right">Approved</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Payment</th>
                        <th class="px-5 py-3">Purchase</th>
                        <th class="px-5 py-3">Date</th>
                        @if(auth()->user()->isAdmin())<th class="px-5 py-3 text-right">Delete</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requisitions as $row)
                        @php
                            $paid = (float) ($row->paid_total ?? 0);
                            $approved = (float) $row->approved_amount;
                            $payStatus = $row->status !== 'approved'
                                ? null
                                : ($paid <= 0 ? 'unpaid' : ($paid < $approved ? 'partial' : 'paid'));

                            $prodCount = (int) $row->product_items_count;
                            $purCount  = (int) $row->purchased_items_count;
                            $purStatus = $prodCount === 0
                                ? null
                                : ($purCount === 0 ? 'not purchased' : ($purCount < $prodCount ? 'partial' : 'purchased'));
                        @endphp
                        <tr class="tbl-row">
                            <td class="px-5 py-3">
                                <a class="inline-flex items-center gap-1.5 font-semibold text-[#287857] hover:underline underline-offset-2"
                                   href="{{ route('requisitions.show', $row) }}">
                                    <span class="rounded-md bg-emerald-50 px-1.5 py-0.5 font-mono text-[11px] text-emerald-700">
                                        {{ $row->requisition_number }}
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
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-slate-800">
                                ৳ {{ number_format($row->total_amount, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-slate-600">
                                {{ $row->approved_amount ? '৳ '.number_format($row->approved_amount, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3">
                                @include('partials.status', ['status' => $row->status])
                            </td>
                            <td class="px-5 py-3">
                                @if($payStatus)
                                    @include('partials.status', ['status' => $payStatus])
                                    @if($payStatus === 'partial')
                                        <span class="mt-1 block text-[10px] text-slate-400">
                                            ৳ {{ number_format($paid, 0) }} / {{ number_format($approved, 0) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($purStatus)
                                    @include('partials.status', ['status' => $purStatus])
                                    @if($purStatus === 'partial')
                                        <span class="mt-1 block text-[10px] text-slate-400">
                                            {{ $purCount }} / {{ $prodCount }} items
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-400">
                                {{ $row->requested_at->format('d M Y') }}
                            </td>
                            @if(auth()->user()->isAdmin())
                                <td class="px-5 py-3 text-right">
                                    @include('partials.delete-button', ['action' => route('requisitions.destroy', $row), 'label' => 'requisition '.$row->requisition_number])
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? 9 : 8 }}" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50">
                                        <svg class="h-7 w-7 text-slate-200" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500">No requisitions found</p>
                                        <p class="mt-0.5 text-[12px] text-slate-400">Try adjusting filters or create a new one.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requisitions->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $requisitions->links() }}</div>
        @endif
    </section>
</x-app-layout>
