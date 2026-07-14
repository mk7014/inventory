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

    // h-full + flex column: cards in a grid row stretch to the tallest one, and the
    // pieces inside line up across all of them instead of floating at their own heights.
    $shell = 'flex h-full flex-col rounded-2xl border bg-white p-5 text-left shadow-sm '
        .'transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md '.$border;
@endphp

<{{ $tag }}
    @if ($metric)
        type="button"
        class="js-metric group w-full cursor-pointer {{ $shell }}"
        data-metric="{{ $metric }}"
    @else
        class="{{ $shell }}"
    @endif
>
    {{-- Label + icon. min-h reserves two lines, so a one-line label leaves the same
         gap as a wrapping one and every value below starts on the same baseline. --}}
    <div class="flex items-start justify-between gap-3">
        <p class="min-h-8 text-[11px] font-semibold uppercase leading-4 tracking-wider text-slate-400">
            {{ $label }}
        </p>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $iconTone }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
            </svg>
        </span>
    </div>

    <p class="mt-1 truncate text-xl font-bold tracking-tight text-[#17211c]">{{ $value }}</p>

    @if ($hint)
        {{-- Two lines, wrapped rather than cut off mid-word. --}}
        <p class="mt-1 min-h-8 text-[11px] font-medium leading-4 text-slate-400">{{ $hint }}</p>
    @endif

    @if ($metric)
        {{-- mt-auto pins this to the bottom, so the links align across the row. --}}
        <p class="mt-auto pt-2.5 inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400
                  transition-colors group-hover:text-indigo-600">
            <span>See the details</span>
            <svg class="h-3 w-3 shrink-0 transition-transform group-hover:translate-x-0.5"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </p>
    @endif
</{{ $tag }}>
