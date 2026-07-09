@php
    $classes = [
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-red-100 text-red-800',
        'hold' => 'bg-slate-200 text-slate-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        // Sales lifecycle
        'confirmed' => 'bg-sky-100 text-sky-800',
        'send_to_courier' => 'bg-violet-100 text-violet-800',
        'shipped' => 'bg-indigo-100 text-indigo-800',
        'delivered' => 'bg-emerald-100 text-emerald-800',
        'returned' => 'bg-red-100 text-red-800',
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-slate-200 text-slate-700',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'partial' => 'bg-amber-100 text-amber-800',
        'unpaid' => 'bg-rose-100 text-rose-700',
        'purchased' => 'bg-emerald-100 text-emerald-800',
        'not purchased' => 'bg-slate-200 text-slate-700',
        'cancelled' => 'bg-slate-200 text-slate-700',
        'due' => 'bg-rose-100 text-rose-700',
    ][$status] ?? 'bg-slate-100 text-slate-700';

    // Friendly labels for multi-word statuses; falls back to ucfirst otherwise.
    $label = [
        'send_to_courier' => 'Send To Courier',
    ][$status] ?? ucfirst($status);
@endphp
<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $classes }}">{{ $label }}</span>
