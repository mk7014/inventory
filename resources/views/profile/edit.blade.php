<x-app-layout title="My Profile">
    @include('partials.page-header', [
        'title'    => 'My Profile',
        'subtitle' => 'Manage your personal information, photo and password',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── Account overview ─────────────────────────────────────── --}}
        <aside class="xl:col-span-1">
            <div class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm">
                <div class="h-20" style="background: linear-gradient(135deg,#34d399,#059669);"></div>
                <div class="-mt-10 flex flex-col items-center px-5 pb-6 text-center">
                    <div class="h-20 w-20 overflow-hidden rounded-full ring-4 ring-white bg-emerald-100">
                        @if($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-emerald-400 to-emerald-600 text-xl font-bold text-white">
                                {{ $user->initials() }}
                            </div>
                        @endif
                    </div>
                    <h2 class="mt-3 text-[15px] font-bold text-[#17211c]">{{ $user->name }}</h2>
                    <span class="mt-1 inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">
                        {{ $user->role?->name ?? 'No role' }}
                    </span>

                    <dl class="mt-5 w-full space-y-3 text-left">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 px-3.5 py-2.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <div class="min-w-0">
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Email</dt>
                                <dd class="truncate text-[12px] font-medium text-slate-700">{{ $user->email }}</dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 px-3.5 py-2.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Member Since</dt>
                                <dd class="text-[12px] font-medium text-slate-700">{{ $user->created_at?->format('d M Y') }}</dd>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 px-3.5 py-2.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Balance</dt>
                                <dd class="text-[12px] font-bold {{ (float) $user->balance < 0 ? 'text-red-600' : 'text-emerald-700' }}">৳ {{ number_format($user->balance, 2) }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>
        </aside>

        {{-- ── Editable sections ────────────────────────────────────── --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Personal information + photo --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-[13px] font-bold text-[#17211c]">Personal Information</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Update your name, email and profile photo</p>
                </div>
                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5 p-5">
                    @csrf @method('put')

                    {{-- Avatar uploader --}}
                    <div class="flex items-center gap-4">
                        <div id="avatar-preview" class="h-16 w-16 shrink-0 overflow-hidden rounded-full ring-2 ring-slate-100 bg-emerald-100">
                            @if($user->avatarUrl())
                                <img src="{{ $user->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-linear-to-br from-emerald-400 to-emerald-600 text-lg font-bold text-white">{{ $user->initials() }}</div>
                            @endif
                        </div>
                        <div>
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-[12px] font-semibold text-slate-600 transition hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Choose Photo
                                <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="hidden" id="avatar-input">
                            </label>
                            <p class="mt-1 text-[10px] text-slate-400">JPG, PNG or WEBP · max 2MB</p>
                            @error('avatar', 'updateProfile')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Full Name <span class="text-red-400">*</span></label>
                        <input name="name" required value="{{ old('name', $user->name) }}"
                               class="ppp-field">
                        @error('name', 'updateProfile')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Email <span class="text-red-400">*</span></label>
                        <input name="email" type="email" required value="{{ old('email', $user->email) }}"
                               class="ppp-field">
                        @error('email', 'updateProfile')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-1">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Save Changes
                        </button>
                    </div>
                </form>
                @if($user->avatarUrl())
                <div class="border-t border-slate-100 px-5 py-3">
                    <form method="post" action="{{ route('profile.avatar.delete') }}"
                          onsubmit="return confirm('Remove your profile photo?');">
                        @csrf @method('delete')
                        <button class="text-[11px] font-semibold text-rose-600 transition hover:underline underline-offset-2">Remove current photo</button>
                    </form>
                </div>
                @endif
            </div>

            {{-- Change password --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-[13px] font-bold text-[#17211c]">Change Password</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Use a strong password you don't use elsewhere</p>
                </div>
                <form method="post" action="{{ route('profile.password.update') }}" class="space-y-5 p-5">
                    @csrf @method('put')
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Current Password <span class="text-red-400">*</span></label>
                        <input name="current_password" type="password" required autocomplete="current-password"
                               class="ppp-field">
                        @error('current_password', 'updatePassword')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">New Password <span class="text-red-400">*</span></label>
                            <input name="password" type="password" required autocomplete="new-password" placeholder="Min 8 characters"
                                   class="ppp-field">
                            @error('password', 'updatePassword')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">Confirm New Password <span class="text-red-400">*</span></label>
                            <input name="password_confirmation" type="password" required autocomplete="new-password"
                                   class="ppp-field">
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const input = document.getElementById('avatar-input');
        const preview = document.getElementById('avatar-preview');
        if (!input || !preview) return;
        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            preview.innerHTML = '<img src="' + url + '" alt="" class="h-full w-full object-cover">';
        });
    })();
    </script>
    @endpush
</x-app-layout>
