@props([
    'items' => [],
    'tone' => 'sky',
    'empty' => 'Nothing recorded in this period.',
])

@php
    $bars = [
        'sky'    => 'bg-sky-500',
        'rose'   => 'bg-rose-500',
        'indigo' => 'bg-indigo-500',
    ];

    $bar = $bars[$tone] ?? 'bg-slate-400';
@endphp

@if (empty($items))
    <p class="mt-5 rounded-xl bg-slate-50 py-6 text-center text-[12px] font-medium text-slate-400">
        {{ $empty }}
    </p>
@else
    <div class="mt-5 space-y-3.5">
        @foreach ($items as $item)
            <div>
                <div class="flex items-baseline justify-between gap-2 text-[12px]">
                    <span class="truncate font-medium text-slate-600">{{ $item['label'] }}</span>
                    <span class="shrink-0">
                        <span class="font-bold text-slate-700">৳ {{ number_format($item['total'], 2) }}</span>
                        <span class="ml-1 text-slate-400">{{ $item['percent'] }}%</span>
                    </span>
                </div>
                <div class="mt-1.5 flex items-center gap-2">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full {{ $bar }} transition-all duration-700"
                             style="width: {{ min(100, $item['percent']) }}%"></div>
                    </div>
                    <span class="w-16 shrink-0 text-right text-[10px] font-medium text-slate-400">
                        {{ $item['transactions'] }} {{ Str::plural('txn', $item['transactions']) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@endif
