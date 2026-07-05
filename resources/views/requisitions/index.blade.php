<x-app-layout title="Requisitions">
    @include('partials.page-header', [
        'title'    => 'Requisitions',
        'subtitle' => 'Request, approve, hold, reject, and pay product purchase funds',
        'actions'  => '<a href="'.route('requisitions.create').'"
                          class="inline-flex items-center gap-1.5 rounded-xl bg-[#287857] px-4 py-2.5
                                 text-[12px] font-semibold text-white shadow-sm transition hover:bg-[#1f6046]">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                           </svg>
                           New Requisition
                       </a>',
    ])

    {{-- Filter bar --}}
    <form method="get"
          class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</label>
            <select name="status"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                           focus:border-[#287857] focus:outline-none">
                <option value="">All status</option>
                @foreach(['pending','approved','rejected','hold'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                          focus:border-[#287857] focus:outline-none">
        </div>
        <div class="flex-1 min-w-36">
            <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600
                          focus:border-[#287857] focus:outline-none">
        </div>
        <button class="rounded-xl bg-[#17211c] px-5 py-2.5 text-[12px] font-semibold text-white
                       transition hover:bg-black">
            Filter
        </button>
    </form>

    {{-- Table --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">All Requisitions</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $requisitions->total() }} records</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Req No</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Requested</th>
                        <th class="px-5 py-3">Approved</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requisitions as $row)
                        <tr class="tbl-row">
                            <td class="px-5 py-3">
                                <a class="font-semibold text-[#287857] hover:underline underline-offset-2"
                                   href="{{ route('requisitions.show', $row) }}">
                                    {{ $row->requisition_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $row->employee->name }}</td>
                            <td class="px-5 py-3 font-medium text-slate-800">
                                ৳ {{ number_format($row->total_amount, 2) }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $row->approved_amount ? '৳ '.number_format($row->approved_amount, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3">
                                @include('partials.status', ['status' => $row->status])
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $row->requested_at->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-sm text-slate-400">No requisitions found.</span>
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
