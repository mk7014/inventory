<x-app-layout title="Due Purchase Report">
    @php $isAdmin = auth()->user()->isAdmin(); $outstanding = $totalDue - $totalPaid; @endphp
    @include('partials.page-header', [
        'title'    => 'Due Purchase Report',
        'subtitle' => 'Out-of-pocket purchases the company still owes employees',
    ])

    {{-- Totals --}}
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Due Purchases</p>
            <p class="mt-1 text-xl font-bold text-slate-800">৳ {{ number_format($totalDue, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Paid</p>
            <p class="mt-1 text-xl font-bold text-emerald-700">৳ {{ number_format($totalPaid, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-rose-400">Outstanding Due</p>
            <p class="mt-1 text-xl font-bold text-rose-600">৳ {{ number_format($outstanding, 2) }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="get" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200/60 bg-white px-5 py-4 shadow-sm">
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
        <div class="flex items-center gap-2 pb-0.5">
            <input type="checkbox" name="outstanding" value="1" id="outstandingOnly" @checked(request('outstanding'))
                   class="h-4 w-4 rounded border-slate-300 text-[#287857] focus:ring-[#287857]">
            <label for="outstandingOnly" class="text-[12px] text-slate-600">Outstanding only</label>
        </div>
        <button class="rounded-xl bg-[#17211c] px-5 py-2.5 text-[12px] font-semibold text-white transition hover:bg-black">Filter</button>
    </form>

    {{-- Table --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">DP No</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Grand Total</th>
                        <th class="px-5 py-3">Paid</th>
                        <th class="px-5 py-3">Due</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $row)
                        <tr class="tbl-row">
                            <td class="px-5 py-3">
                                <a class="font-semibold text-[#287857] hover:underline underline-offset-2" href="{{ route('direct-purchases.show', $row) }}">{{ $row->purchase_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $row->employee->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $row->supplier?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-800">৳ {{ number_format($row->grand_total, 2) }}</td>
                            <td class="px-5 py-3 text-emerald-700">৳ {{ number_format($row->paid_amount, 2) }}</td>
                            <td class="px-5 py-3 {{ $row->dueAmount() > 0 ? 'font-semibold text-rose-600' : 'text-slate-400' }}">৳ {{ number_format($row->dueAmount(), 2) }}</td>
                            <td class="px-5 py-3">@include('partials.status', ['status' => $row->payment_status])</td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ $row->purchase_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-14 text-center text-sm text-slate-400">No due purchases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $purchases->links() }}</div>
        @endif
    </section>
</x-app-layout>
