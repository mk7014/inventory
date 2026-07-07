@php
    $classes = [
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'hold' => 'bg-slate-200 text-slate-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'returned' => 'bg-red-100 text-red-800',
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-200 text-slate-700',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'partial' => 'bg-amber-100 text-amber-800',
        'unpaid' => 'bg-rose-100 text-rose-700',
        'purchased' => 'bg-emerald-100 text-emerald-800',
        'not purchased' => 'bg-slate-200 text-slate-700',
    ][$status] ?? 'bg-slate-100 text-slate-700';
@endphp
<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $classes }}">{{ ucfirst($status) }}</span>
