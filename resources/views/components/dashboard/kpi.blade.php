@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'slate',
    'metric' => null,   // when set, the card opens the drill-down drawer for this metric
    'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
])

@php
    $tones = [
        'emerald' => ['border-emerald-100', 'bg-emerald-50 text-emerald-600'],
        'sky'     => ['border-sky-100', 'bg-sky-50 text-sky-600'],
        'rose'    => ['border-rose-100', 'bg-rose-50 text-rose-600'],
        'amber'   => ['border-amber-100', 'bg-amber-50 text-amber-600'],
        'indigo'  => ['border-indigo-100', 'bg-indigo-50 text-indigo-600'],
        'slate'   => ['border-slate-200/60', 'bg-slate-100 text-slate-600'],
    ];

    [$border, $iconTone] = $tones[$tone] ?? $tones['slate'];

    $tag = $metric ? 'button' : 'div';
@endphp

<{{ $tag }}
    @if ($metric)
        type="button"
        class="js-metric group w-full cursor-pointer rounded-2xl border bg-white p-5 text-left shadow-sm
               transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $border }}"
        data-metric="{{ $metric }}"
    @else
        class="rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $border }}"
    @endif
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
            <p class="mt-2 truncate text-xl font-bold tracking-tight text-[#17211c]">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 truncate text-[11px] font-medium text-slate-400">{{ $hint }}</p>
            @endif

            @if ($metric)
                <p class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400
                          transition-colors group-hover:text-indigo-600">
                    Click to see where this came from
                    <svg class="h-3 w-3 transition-transform group-hover:translate-x-0.5"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </p>
            @endif
        </div>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconTone }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
            </svg>
        </span>
    </div>
</{{ $tag }}>
