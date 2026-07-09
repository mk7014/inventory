<x-app-layout title="Direct Purchase Payments">
    @include('partials.page-header', [
        'title'    => 'Direct Purchase Payments',
        'subtitle' => 'Reimbursements recorded against due purchases',
    ])

    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Payment History</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $payments->total() }} payments</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">DP No</th>
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Method</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Paid By</th>
                        <th class="px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $methodColors = [
                            'bkash' => 'bg-pink-50 text-pink-700', 'nagad' => 'bg-orange-50 text-orange-700',
                            'bank' => 'bg-indigo-50 text-indigo-700', 'cash' => 'bg-emerald-50 text-emerald-700',
                        ];
                    @endphp
                    @forelse($payments as $payment)
                        <tr class="tbl-row">
                            <td class="px-5 py-3">
                                <a class="font-semibold text-[#287857] hover:underline underline-offset-2" href="{{ route('direct-purchases.show', $payment->directPurchase) }}">{{ $payment->directPurchase->purchase_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $payment->paidTo->name }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $payment->directPurchase->supplier?->name ?? '—' }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-800">৳ {{ number_format($payment->amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $methodColors[$payment->payment_method] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($payment->payment_method) }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ $payment->reference ?: '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $payment->paidBy->name }}</td>
                            <td class="px-5 py-3 text-xs text-slate-400">{{ $payment->payment_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-14 text-center text-sm text-slate-400">No payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $payments->links() }}</div>
        @endif
    </section>
</x-app-layout>
