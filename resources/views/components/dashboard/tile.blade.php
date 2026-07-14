@props([
    'label',
    'count',
    'unit' => 'items',
    'hint' => null,
    'tone' => 'slate',
    'metric' => null,
])

@php
    $tones = [
        'emerald' => ['border-emerald-100 hover:border-emerald-300', 'text-emerald-700'],
        'sky'     => ['border-sky-100 hover:border-sky-300', 'text-sky-700'],
        'amber'   => ['border-amber-100 hover:border-amber-300', 'text-amber-700'],
        'indigo'  => ['border-indigo-100 hover:border-indigo-300', 'text-indigo-700'],
        'slate'   => ['border-slate-200 hover:border-slate-300', 'text-slate-700'],
    ];

    [$border, $countTone] = $tones[$tone] ?? $tones['slate'];
@endphp

<button type="button"
        class="js-metric group rounded-xl border bg-slate-50/70 p-3.5 text-left transition-all duration-200
               hover:-translate-y-0.5 hover:bg-white hover:shadow-md {{ $border }}"
        @if ($metric) data-metric="{{ $metric }}" @endif>

    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>

    <p class="mt-1.5 flex items-baseline gap-1">
        <span class="text-xl font-bold tracking-tight {{ $countTone }}">{{ number_format($count) }}</span>
        <span class="text-[11px] font-medium text-slate-400">{{ $unit }}</span>
    </p>

    @if ($hint)
        <p class="mt-1 text-[11px] font-medium leading-snug text-slate-400">{{ $hint }}</p>
    @endif

    <p class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold text-slate-300 transition-colors group-hover:text-indigo-600">
        See the list
        <svg class="h-3 w-3 transition-transform group-hover:translate-x-0.5"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </p>
</button>
