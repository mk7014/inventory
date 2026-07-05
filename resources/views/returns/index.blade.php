<x-app-layout title="Returns">
    @include('partials.page-header', [
        'title'    => 'Returns',
        'subtitle' => 'Good returns increase stock; damaged returns are tracked as loss',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- New Return Form --}}
        <div class="xl:col-span-1">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                        <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[13px] font-bold text-[#17211c]">New Return</h2>
                        <p class="text-[11px] text-slate-400">Log a returned sale item</p>
                    </div>
                </div>
                <form method="post" action="{{ route('returns.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Completed Sale <span class="text-red-400">*</span>
                        </label>
                        <select name="sale_id" required
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                       text-slate-700 focus:border-rose-400 focus:outline-none">
                            <option value="">Select sale…</option>
                            @foreach($sales as $sale)
                                <option value="{{ $sale->id }}">
                                    {{ $sale->sold_date->format('d M') }} · {{ $sale->product_name }} · Qty {{ $sale->quantity }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Return Quantity <span class="text-red-400">*</span>
                        </label>
                        <input name="quantity" type="number" min="1" value="1" required
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 focus:border-rose-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Condition <span class="text-red-400">*</span>
                        </label>
                        <div class="flex rounded-xl border border-slate-200 overflow-hidden">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="condition" value="good" class="sr-only" checked>
                                <span class="cond-good flex items-center justify-center gap-1.5 px-3 py-2.5
                                             text-[12px] font-semibold transition-all duration-200
                                             bg-emerald-500 text-white">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Good
                                </span>
                            </label>
                            <label class="flex-1 cursor-pointer border-l border-slate-200">
                                <input type="radio" name="condition" value="damaged" class="sr-only">
                                <span class="cond-damaged flex items-center justify-center gap-1.5 px-3 py-2.5
                                             text-[12px] font-semibold transition-all duration-200
                                             text-slate-500 hover:bg-slate-50">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Damaged
                                </span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Return Date <span class="text-red-400">*</span>
                        </label>
                        <input name="return_date" type="date" value="{{ now()->toDateString() }}" required
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                      text-slate-700 focus:border-rose-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Reason
                        </label>
                        <textarea name="reason" rows="3"
                                  placeholder="Why was this returned?"
                                  class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2.5 text-sm
                                         text-slate-700 placeholder-slate-300 focus:border-rose-400 focus:outline-none"></textarea>
                    </div>
                    <button class="w-full rounded-xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white
                                   shadow-sm transition hover:bg-rose-700 flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Return
                    </button>
                </form>
            </div>
        </div>

        {{-- Returns Table --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">Return History</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $returns->total() }} records</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-160 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Qty</th>
                            <th class="px-5 py-3">Condition</th>
                            <th class="px-5 py-3">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($returns as $return)
                            <tr class="tbl-row">
                                <td class="px-5 py-3 text-xs text-slate-400">
                                    {{ $return->return_date->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $return->product_name }}</td>
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $return->quantity }}</td>
                                <td class="px-5 py-3">
                                    @if($return->condition === 'good')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1
                                                     text-[10px] font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            Good
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1
                                                     text-[10px] font-semibold text-rose-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                                            Damaged
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-400 max-w-45 truncate">
                                    {{ $return->reason ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                        <span class="text-sm text-slate-400">No returns found.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($returns->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $returns->links() }}</div>
            @endif
        </section>
    </div>

    @push('scripts')
    <script>
        $('input[name="condition"]').on('change', function () {
            $('.cond-good, .cond-damaged').removeClass('bg-emerald-500 bg-rose-500 text-white')
                                          .addClass('text-slate-500');
            if (this.value === 'good') {
                $('.cond-good').removeClass('text-slate-500').addClass('bg-emerald-500 text-white');
            } else {
                $('.cond-damaged').removeClass('text-slate-500').addClass('bg-rose-500 text-white');
            }
        });
    </script>
    @endpush
</x-app-layout>
