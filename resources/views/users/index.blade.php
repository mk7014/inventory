<x-app-layout title="Users">
    @include('partials.page-header', [
        'title'    => 'Users',
        'subtitle' => 'Admin and employee access management',
    ])

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Add User Form --}}
        <div class="xl:col-span-1">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-violet-50">
                        <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[13px] font-bold text-[#17211c]">Add User</h2>
                        <p class="text-[11px] text-slate-400">Create a new system user</p>
                    </div>
                </div>
                <form method="post" action="{{ route('users.store') }}" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Full Name <span class="text-red-400">*</span>
                        </label>
                        <input name="name" required placeholder="e.g. Rahim Uddin"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Email <span class="text-red-400">*</span>
                        </label>
                        <input name="email" type="email" required placeholder="user@example.com"
                               class="ppp-field">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Password <span class="text-red-400">*</span>
                        </label>
                        <input name="password" type="password" required placeholder="Min 8 characters"
                               class="ppp-field">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Role <span class="text-red-400">*</span>
                            </label>
                            <select name="role_id" required
                                    class="ppp-field">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected($role->slug === 'employee')>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                Status
                            </label>
                            <select name="status"
                                    class="ppp-field">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button class="w-full rounded-xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white
                                   shadow-sm transition hover:bg-violet-700 flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create User
                    </button>
                </form>
            </div>
        </div>

        {{-- Users Table --}}
        <section class="xl:col-span-2 rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-[13px] font-bold text-[#17211c]">All Users</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $users->total() }} users</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-200 text-left text-sm">
                    <thead class="bg-slate-50/70">
                        <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">New Password</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $user)
                            <tr class="tbl-row align-middle">
                                <form method="post" action="{{ route('users.update', $user) }}">
                                    @csrf @method('put')
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full
                                                        text-[10px] font-bold text-white
                                                        {{ $user->isVoided()
                                                            ? 'bg-slate-300'
                                                            : 'bg-linear-to-br from-violet-400 to-violet-600' }}">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <input name="name" value="{{ $user->name }}" class="ppp-field">
                                            @if($user->isVoided())
                                                <span class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold
                                                             uppercase tracking-wide text-amber-700"
                                                      title="Voided {{ $user->voided_at->format('d M Y') }} — excluded from all calculations">
                                                    Voided
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="email" type="email" value="{{ $user->email }}"
                                               class="ppp-field">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select name="role_id"
                                                class="ppp-field">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <select name="status"
                                                class="ppp-field">
                                            <option value="active"   @selected($user->status === 'active')>Active</option>
                                            <option value="inactive" @selected($user->status === 'inactive')>Inactive</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <input name="password" type="password"
                                               placeholder="Keep current"
                                               class="w-32 rounded-lg border border-slate-200 px-2.5 py-2 text-sm
                                                      text-slate-600 placeholder-slate-300 focus:border-violet-400 focus:outline-none">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <button class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5
                                                           text-[11px] font-semibold text-violet-700 transition
                                                           hover:bg-violet-600 hover:text-white hover:border-transparent">
                                                Save
                                            </button>

                                            @can('users.void')
                                                {{-- type=button: opens its own form below, never submits the update form --}}
                                                <button type="button"
                                                        class="js-void-user rounded-lg border px-3 py-1.5 text-[11px] font-semibold transition
                                                               disabled:cursor-not-allowed disabled:opacity-40
                                                               {{ $user->isVoided()
                                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-transparent hover:bg-emerald-600 hover:text-white'
                                                                    : 'border-amber-200 bg-amber-50 text-amber-700 hover:border-transparent hover:bg-amber-600 hover:text-white' }}"
                                                        @disabled($user->id === auth()->id())
                                                        data-name="{{ $user->name }}"
                                                        data-voided="{{ $user->isVoided() ? '1' : '0' }}"
                                                        data-action="{{ $user->isVoided() ? route('users.restore', $user) : route('users.void', $user) }}">
                                                    {{ $user->isVoided() ? 'Restore' : 'Void' }}
                                                </button>
                                            @endcan

                                            @can('users.delete')
                                                @php $impact = $impacts[$user->id]; @endphp
                                                {{-- type=button so it cannot submit the surrounding update form --}}
                                                <button type="button"
                                                        class="js-delete-user rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5
                                                               text-[11px] font-semibold text-rose-700 transition
                                                               hover:border-transparent hover:bg-rose-600 hover:text-white
                                                               disabled:cursor-not-allowed disabled:opacity-40"
                                                        @disabled($user->id === auth()->id())
                                                        title="{{ $user->id === auth()->id() ? 'You cannot delete your own account' : 'Delete this user and all their records' }}"
                                                        data-id="{{ $user->id }}"
                                                        data-name="{{ $user->name }}"
                                                        data-action="{{ route('users.destroy', $user) }}"
                                                        data-impact='@json($impact)'>
                                                    Delete
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $users->links() }}</div>
            @endif
        </section>
    </div>

    @can('users.void')
    {{-- ── Void / Restore confirmation ────────────────────────────────
         Reversible, so it needs a plain confirm rather than the typed-name
         gate the destructive delete uses. --}}
    <div id="voidUserModal" class="fixed inset-0 z-50 hidden">
        <div id="voidUserBackdrop"
             class="absolute inset-0 bg-slate-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-200"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div id="voidUserPanel"
                 class="w-full max-w-md scale-95 overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl
                        transition-all duration-200">

                <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4">
                    <div id="vuIconWrap" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50">
                        <svg id="vuIcon" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728A9 9 0 015.636 5.636"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 id="vuTitle" class="text-[15px] font-bold text-[#17211c]"></h2>
                        <p id="vuLead" class="mt-0.5 text-[12px] text-slate-500"></p>
                    </div>
                </div>

                <div class="p-5">
                    <ul id="vuEffects" class="space-y-1.5 rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-[12px] text-slate-600"></ul>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" id="vuCancel"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold
                                       text-slate-600 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <form id="voidUserForm" method="post">
                            @csrf
                            <button type="submit" id="vuSubmit"
                                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition"></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // ── Void / Restore ──────────────────────────────────────────────
        const vuModal    = document.getElementById('voidUserModal');
        const vuBackdrop = document.getElementById('voidUserBackdrop');
        const vuPanel    = document.getElementById('voidUserPanel');
        const vuForm     = document.getElementById('voidUserForm');
        const vuSubmit   = document.getElementById('vuSubmit');

        const VOID_EFFECTS = [
            'Their sales leave revenue and profit',
            'Their purchases leave the product cost basis',
            'Their expenses leave operating costs',
            'Their wallet leaves the fund &amp; spend totals',
            'They can no longer log in',
        ];
        const RESTORE_EFFECTS = [
            'Their sales count towards revenue again',
            'Their purchases return to the cost basis',
            'Their expenses return to operating costs',
            'They can log in again',
        ];

        function openVoidUser(button) {
            const name    = button.dataset.name;
            const voided  = button.dataset.voided === '1';
            const effects = voided ? RESTORE_EFFECTS : VOID_EFFECTS;

            vuForm.action = button.dataset.action;

            document.getElementById('vuTitle').textContent = (voided ? 'Restore ' : 'Void ') + name + '?';
            document.getElementById('vuLead').textContent = voided
                ? 'Their records will count in every calculation again.'
                : 'Their records stay on file for audit, but stop counting anywhere. Stock is not affected — the goods really moved.';

            document.getElementById('vuEffects').innerHTML = effects
                .map((line) => `<li class="flex gap-2"><span class="text-slate-300">•</span><span>${line}</span></li>`)
                .join('');

            const wrap = document.getElementById('vuIconWrap');
            const icon = document.getElementById('vuIcon');
            wrap.className = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ' + (voided ? 'bg-emerald-50' : 'bg-amber-50');
            icon.className = 'h-5 w-5 ' + (voided ? 'text-emerald-600' : 'text-amber-600');

            vuSubmit.textContent = voided ? 'Restore user' : 'Void user';
            vuSubmit.className = 'rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition '
                + (voided ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700');

            vuModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                vuBackdrop.classList.remove('opacity-0');
                vuPanel.classList.remove('opacity-0', 'scale-95');
            });
        }

        function closeVoidUser() {
            vuBackdrop.classList.add('opacity-0');
            vuPanel.classList.add('opacity-0', 'scale-95');
            document.body.style.overflow = '';
            setTimeout(() => vuModal.classList.add('hidden'), 200);
        }

        document.querySelectorAll('.js-void-user').forEach((button) => {
            button.addEventListener('click', () => openVoidUser(button));
        });
        document.getElementById('vuCancel').addEventListener('click', closeVoidUser);
        vuBackdrop.addEventListener('click', closeVoidUser);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !vuModal.classList.contains('hidden')) closeVoidUser();
        });
    </script>
    @endpush
    @endcan

    @can('users.delete')
    {{-- ── Delete confirmation ────────────────────────────────────────
         One shared dialog, filled from the clicked row's data-* attributes.
         It lives outside the table because each row is already wrapped in the
         update <form>, and forms cannot nest. --}}
    <div id="deleteUserModal" class="fixed inset-0 z-50 hidden">
        <div id="deleteUserBackdrop"
             class="absolute inset-0 bg-slate-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-200"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">
            <div id="deleteUserPanel"
                 class="w-full max-w-lg scale-95 overflow-hidden rounded-2xl bg-white opacity-0 shadow-2xl
                        transition-all duration-200">

                <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                        <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-[15px] font-bold text-[#17211c]">
                            Delete <span id="duName" class="text-rose-700"></span>?
                        </h2>
                        <p class="mt-0.5 text-[12px] text-slate-500">
                            This permanently removes the user and everything below. It cannot be undone.
                        </p>
                    </div>
                </div>

                <form id="deleteUserForm" method="post" class="p-5">
                    @csrf
                    @method('delete')

                    @error('confirm_name')
                        <p class="mb-3 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-[12px] font-medium text-rose-700">
                            {{ $message }}
                        </p>
                    @enderror

                    {{-- Blast radius --}}
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            Will be permanently deleted
                        </p>
                        <ul id="duImpact" class="space-y-1.5 text-[12px]"></ul>
                        <p id="duStockNote" class="mt-2.5 hidden border-t border-slate-200/70 pt-2.5 text-[11px] text-amber-700">
                            Deleting these sales and purchases will also reverse the stock they moved.
                        </p>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Type <span id="duNameEcho" class="font-bold text-slate-600"></span> to confirm
                        </label>
                        <input name="confirm_name" id="duConfirm" autocomplete="off" required
                               placeholder="Enter the user's name exactly" class="ppp-field">
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" id="duCancel"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold
                                       text-slate-600 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" id="duSubmit" disabled
                                class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                                       transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                            Delete permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const duModal    = document.getElementById('deleteUserModal');
        const duBackdrop = document.getElementById('deleteUserBackdrop');
        const duPanel    = document.getElementById('deleteUserPanel');
        const duForm     = document.getElementById('deleteUserForm');
        const duConfirm  = document.getElementById('duConfirm');
        const duSubmit   = document.getElementById('duSubmit');
        const duImpact   = document.getElementById('duImpact');

        // label → [count, extra]. Only non-zero rows are listed, so the dialog stays honest.
        const IMPACT_ROWS = [
            ['sales',            'Sales'],
            ['returns',          'Returns on those sales'],
            ['requisitions',     'Requisitions (with items & payments)'],
            ['payments',         'Payments received'],
            ['direct_purchases', 'Direct purchases'],
            ['expenses',         'Expenses'],
            ['ledger_rows',      'Wallet ledger entries'],
        ];

        const taka = (value) => '৳ ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        let duTargetName = '';

        function openDeleteUser(button) {
            const impact = JSON.parse(button.dataset.impact);
            duTargetName = button.dataset.name;

            duForm.action = button.dataset.action;
            document.getElementById('duName').textContent = duTargetName;
            document.getElementById('duNameEcho').textContent = duTargetName;

            duImpact.innerHTML = '';
            let anything = false;

            IMPACT_ROWS.forEach(([key, label]) => {
                if (!impact[key]) return;
                anything = true;
                duImpact.insertAdjacentHTML('beforeend',
                    `<li class="flex items-center justify-between gap-3">
                        <span class="text-slate-600">${label}</span>
                        <span class="font-bold text-rose-700">${impact[key]}</span>
                     </li>`);
            });

            if (impact.sale_revenue > 0) {
                duImpact.insertAdjacentHTML('beforeend',
                    `<li class="flex items-center justify-between gap-3 border-t border-slate-200/70 pt-1.5">
                        <span class="font-medium text-slate-600">Delivered revenue removed</span>
                        <span class="font-bold text-rose-700">${taka(impact.sale_revenue)}</span>
                     </li>`);
            }

            if (Number(impact.balance) !== 0) {
                duImpact.insertAdjacentHTML('beforeend',
                    `<li class="flex items-center justify-between gap-3">
                        <span class="font-medium text-slate-600">Wallet balance written off</span>
                        <span class="font-bold text-rose-700">${taka(impact.balance)}</span>
                     </li>`);
            }

            if (!anything) {
                duImpact.innerHTML = '<li class="py-1 text-slate-400">No related records — only the account itself.</li>';
            }

            document.getElementById('duStockNote').classList.toggle('hidden', !impact.sales && !impact.direct_purchases);

            duConfirm.value = '';
            duSubmit.disabled = true;

            duModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                duBackdrop.classList.remove('opacity-0');
                duPanel.classList.remove('opacity-0', 'scale-95');
            });
            duConfirm.focus();
        }

        function closeDeleteUser() {
            duBackdrop.classList.add('opacity-0');
            duPanel.classList.add('opacity-0', 'scale-95');
            document.body.style.overflow = '';
            setTimeout(() => duModal.classList.add('hidden'), 200);
        }

        // The submit button only unlocks on an exact name match — the server re-checks
        // this too, so a tampered DOM buys nothing.
        duConfirm.addEventListener('input', () => {
            duSubmit.disabled = duConfirm.value !== duTargetName;
        });

        document.querySelectorAll('.js-delete-user').forEach((button) => {
            button.addEventListener('click', () => openDeleteUser(button));
        });

        document.getElementById('duCancel').addEventListener('click', closeDeleteUser);
        duBackdrop.addEventListener('click', closeDeleteUser);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !duModal.classList.contains('hidden')) closeDeleteUser();
        });

        @if(session('delete_failed_user'))
            // A failed confirmation bounced back — reopen on the exact row it came from.
            const failed = document.querySelector('.js-delete-user[data-id="{{ session('delete_failed_user') }}"]');
            if (failed) openDeleteUser(failed);
        @endif
    </script>
    @endpush
    @endcan
</x-app-layout>
