<x-app-layout title="Payments">
    @include('partials.page-header', [
        'title'    => 'Payments',
        'subtitle' => 'Payment history for approved requisitions',
    ])

    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">

        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Payment History</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $payments->total() }} records</p>
            </div>
            @php $pageTotal = $payments->getCollection()->sum('amount'); @endphp
            <div class="rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-right">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-sky-600">Page Total</p>
                <p class="text-sm font-bold text-sky-700">৳ {{ number_format($pageTotal, 2) }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-180 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Requisition</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                        <tr class="tbl-row">
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $payment->payment_date->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3">
                                <a class="font-semibold text-[#287857] hover:underline underline-offset-2"
                                   href="{{ route('requisitions.show', $payment->requisition) }}">
                                    {{ $payment->requisition->requisition_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                {{ $payment->requisition->employee->name }}
                            </td>
                            <td class="px-5 py-3">
                                @php
                                    $methodClasses = [
                                        'bkash'  => 'bg-pink-50 text-pink-700',
                                        'nagad'  => 'bg-orange-50 text-orange-700',
                                        'bank'   => 'bg-indigo-50 text-indigo-700',
                                        'cash'   => 'bg-emerald-50 text-emerald-700',
                                    ][$payment->payment_method] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $methodClasses }}">
                                    {{ ucfirst($payment->payment_method) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-800">
                                ৳ {{ number_format($payment->amount, 2) }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">
                                {{ $payment->reference ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span class="text-sm text-slate-400">No payments found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $payments->links() }}</div>
        @endif
    </section>
</x-app-layout>
