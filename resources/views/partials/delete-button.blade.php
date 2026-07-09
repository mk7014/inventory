{{--
    Admin-only hard-delete button. Include with the target route already resolved:
        @include('partials.delete-button', ['action' => route('sales.destroy', $sale), 'label' => $sale->product_name])
    Optional: 'label' (shown in the confirm prompt).
--}}
@if(auth()->user()?->isAdmin())
    @php $confirmLabel = addslashes($label ?? 'this record'); @endphp
    <form method="post" action="{{ $action }}" class="inline"
          onsubmit="return confirm('Permanently delete {{ $confirmLabel }}? This also removes its related records and cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" title="Delete permanently"
                class="inline-flex items-center justify-center rounded-lg border border-transparent p-1.5 text-slate-400
                       transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </form>
@endif
