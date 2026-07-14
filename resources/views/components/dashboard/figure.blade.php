@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'slate',
])

@php
    $tones = [
        'emerald' => 'text-emerald-700',
        'rose'    => 'text-rose-600',
        'amber'   => 'text-amber-700',
        'indigo'  => 'text-indigo-700',
        'slate'   => 'text-[#17211c]',
    ];

    $valueTone = $tones[$tone] ?? $tones['slate'];
@endphp

<div class="rounded-xl border border-slate-100 bg-slate-50/70 px-3.5 py-3 transition hover:border-slate-200 hover:bg-slate-50">
    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</p>
    <p class="mt-1 truncate text-[15px] font-bold {{ $valueTone }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-0.5 truncate text-[10px] font-medium text-slate-400">{{ $hint }}</p>
    @endif
</div>
