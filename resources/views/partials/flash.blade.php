@if(session('success'))
    <div class="flash-msg mb-4 flex items-start gap-3 rounded-xl border border-emerald-200/70
                bg-emerald-50 px-4 py-3 shadow-sm">
        <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                    bg-emerald-500 text-white">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="flash-msg mb-4 flex items-start gap-3 rounded-xl border border-red-200/70
                bg-red-50 px-4 py-3 shadow-sm">
        <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                    bg-red-500 text-white">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
@endif

@if($errors->any())
    <div class="flash-msg mb-4 flex items-start gap-3 rounded-xl border border-red-200/70
                bg-red-50 px-4 py-3 shadow-sm">
        <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                    bg-red-500 text-white">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-red-800">Please fix the following:</p>
            <ul class="mt-1 space-y-0.5 list-disc list-inside text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
