@php
    $isEdit    = $role->exists;
    $isAdmin   = $role->exists && $role->slug === 'admin';
    $assigned  = collect($assigned ?? []);
    $totalPerm = collect($modules)->flatten(1)->sum(fn ($m) => count($m['actions']));
@endphp

<form method="post" action="{{ $formAction }}" id="role-form">
    @csrf
    @if($isEdit) @method('put') @endif

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- ── Role details ─────────────────────────────────────── --}}
        <div class="xl:col-span-1 space-y-6">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-[13px] font-bold text-[#17211c]">Role Details</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Name, description &amp; status</p>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Role Name <span class="text-red-400">*</span>
                        </label>
                        <input name="name" required value="{{ old('name', $role->name) }}"
                               placeholder="e.g. Store Supervisor" @disabled($isAdmin)
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700
                                      placeholder-slate-300 focus:border-emerald-400 focus:outline-none disabled:bg-slate-50">
                        @error('name')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Description
                        </label>
                        <textarea name="description" rows="3" placeholder="What is this role for?"
                                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700
                                         placeholder-slate-300 focus:border-emerald-400 focus:outline-none">{{ old('description', $role->description) }}</textarea>
                        @error('description')<p class="mt-1 text-[11px] text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            Status <span class="text-red-400">*</span>
                        </label>
                        <select name="status" @disabled($isAdmin)
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700
                                       focus:border-emerald-400 focus:outline-none disabled:bg-slate-50">
                            <option value="active"   @selected(old('status', $role->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $role->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    @if($isAdmin)
                        <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] text-amber-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3l9 16H3l9-16z"/>
                            </svg>
                            <span>The <strong>Administrator</strong> role always has full access and cannot be limited.</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Permission matrix ────────────────────────────────── --}}
        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden"
                 id="permission-matrix" @if($isAdmin) data-locked="1" @endif>

                {{-- Toolbar --}}
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-[13px] font-bold text-[#17211c]">Permissions</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                <span id="perm-count">{{ $isAdmin ? $totalPerm : $assigned->count() }}</span> of {{ $totalPerm }} selected
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-300"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                                </svg>
                                <input type="text" id="perm-search" placeholder="Search permissions…"
                                       class="w-44 rounded-lg border border-slate-200 py-2 pl-8 pr-3 text-[12px] text-slate-700
                                              placeholder-slate-300 focus:border-emerald-400 focus:outline-none">
                            </div>
                            <button type="button" data-matrix="expand"
                                    class="rounded-lg border border-slate-200 px-2.5 py-2 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">Expand</button>
                            <button type="button" data-matrix="collapse"
                                    class="rounded-lg border border-slate-200 px-2.5 py-2 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">Collapse</button>
                            <button type="button" data-matrix="all"
                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-2 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">Select all</button>
                            <button type="button" data-matrix="none"
                                    class="rounded-lg border border-slate-200 px-2.5 py-2 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">Clear</button>
                        </div>
                    </div>
                </div>

                {{-- Sections --}}
                <div class="divide-y divide-slate-100" id="perm-sections">
                    @foreach($modules as $section => $mods)
                        <div class="perm-section" data-section>
                            <button type="button" data-section-toggle
                                    class="flex w-full items-center justify-between gap-3 px-5 py-3 text-left transition hover:bg-slate-50/70">
                                <span class="flex items-center gap-2.5">
                                    <svg class="section-arrow h-4 w-4 text-slate-400 transition-transform" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <span class="text-[12px] font-bold uppercase tracking-wider text-slate-600">{{ $section }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500"
                                          data-section-count>0</span>
                                </span>
                                <label class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-500"
                                       onclick="event.stopPropagation()">
                                    <input type="checkbox" data-section-all @disabled($isAdmin)
                                           class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                    All
                                </label>
                            </button>

                            <div class="section-body px-3 pb-3">
                                @foreach($mods as $module)
                                    <div class="perm-module rounded-xl border border-slate-100 p-3 mt-1"
                                         data-module data-label="{{ Str::lower($module['label']) }}">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <span class="text-[12px] font-semibold text-[#17211c]">{{ $module['label'] }}</span>
                                            <label class="flex items-center gap-1.5 text-[10px] font-medium text-slate-400">
                                                <input type="checkbox" data-module-all @disabled($isAdmin)
                                                       class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                                Select all
                                            </label>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($module['actions'] as $action => $meta)
                                                @php $pid = $meta['permission']->id; @endphp
                                                <label class="perm-chip inline-flex cursor-pointer items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[11px] font-medium transition"
                                                       data-action-label="{{ Str::lower($meta['label']) }}">
                                                    <input type="checkbox" name="permissions[]" value="{{ $pid }}"
                                                           data-perm
                                                           @checked($isAdmin || $assigned->contains($pid))
                                                           @disabled($isAdmin)
                                                           class="peer h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                                    <span class="text-slate-600 peer-checked:text-emerald-700">{{ $meta['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div id="perm-empty" class="hidden px-5 py-10 text-center text-sm text-slate-400">
                        No permissions match your search.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sticky action bar (always visible) ───────────────────────── --}}
    <div class="sticky bottom-0 z-20 -mx-4 mt-6 border-t border-slate-200/80 bg-white/85
                px-4 py-3 backdrop-blur-lg sm:-mx-6 lg:-mx-8"
         style="box-shadow: 0 -8px 24px rgba(15,23,42,0.06);">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-[12px] text-slate-500">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <span>
                    <span class="font-bold text-[#17211c]" id="perm-count-foot">{{ $isAdmin ? $totalPerm : $assigned->count() }}</span>
                    of {{ $totalPerm }} permissions selected
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('roles.index') }}"
                   class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600
                          transition hover:bg-slate-50">Cancel</a>
                @unless($isAdmin)
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5
                                   text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700
                                   focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $isEdit ? 'Update Role' : 'Create Role' }}
                    </button>
                @endunless
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const matrix = document.getElementById('permission-matrix');
    if (!matrix) return;
    const locked = matrix.dataset.locked === '1';
    const perms = () => Array.from(matrix.querySelectorAll('[data-perm]'));
    const countEl = document.getElementById('perm-count');
    const countFoot = document.getElementById('perm-count-foot');

    function refresh() {
        const total = perms().filter(p => p.checked).length;
        countEl.textContent = total;
        if (countFoot) countFoot.textContent = total;
        matrix.querySelectorAll('[data-module]').forEach(mod => {
            const boxes = mod.querySelectorAll('[data-perm]');
            const checked = mod.querySelectorAll('[data-perm]:checked').length;
            const all = mod.querySelector('[data-module-all]');
            if (all) { all.checked = checked === boxes.length && boxes.length > 0; all.indeterminate = checked > 0 && checked < boxes.length; }
        });
        matrix.querySelectorAll('[data-section]').forEach(sec => {
            const boxes = sec.querySelectorAll('[data-perm]');
            const checked = sec.querySelectorAll('[data-perm]:checked').length;
            const all = sec.querySelector('[data-section-all]');
            const badge = sec.querySelector('[data-section-count]');
            if (badge) badge.textContent = checked;
            if (all) { all.checked = checked === boxes.length && boxes.length > 0; all.indeterminate = checked > 0 && checked < boxes.length; }
        });
    }

    if (!locked) {
        matrix.addEventListener('change', (e) => {
            if (e.target.matches('[data-module-all]')) {
                e.target.closest('[data-module]').querySelectorAll('[data-perm]').forEach(p => p.checked = e.target.checked);
            } else if (e.target.matches('[data-section-all]')) {
                e.target.closest('[data-section]').querySelectorAll('[data-perm]').forEach(p => p.checked = e.target.checked);
            }
            refresh();
        });
        matrix.querySelector('[data-matrix="all"]').addEventListener('click', () => { perms().forEach(p => p.checked = true); refresh(); });
        matrix.querySelector('[data-matrix="none"]').addEventListener('click', () => { perms().forEach(p => p.checked = false); refresh(); });
    }

    // Collapse / expand
    function setOpen(sec, open) { sec.classList.toggle('section-collapsed', !open); }
    matrix.querySelectorAll('[data-section-toggle]').forEach(btn => {
        btn.addEventListener('click', () => setOpen(btn.closest('[data-section]'), btn.closest('[data-section]').classList.contains('section-collapsed')));
    });
    matrix.querySelector('[data-matrix="expand"]').addEventListener('click', () => matrix.querySelectorAll('[data-section]').forEach(s => setOpen(s, true)));
    matrix.querySelector('[data-matrix="collapse"]').addEventListener('click', () => matrix.querySelectorAll('[data-section]').forEach(s => setOpen(s, false)));

    // Search
    document.getElementById('perm-search').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        let anyVisible = false;
        matrix.querySelectorAll('[data-section]').forEach(sec => {
            let secVisible = false;
            sec.querySelectorAll('[data-module]').forEach(mod => {
                const label = mod.dataset.label;
                const actions = Array.from(mod.querySelectorAll('[data-action-label]')).map(a => a.dataset.actionLabel).join(' ');
                const hit = !q || label.includes(q) || actions.includes(q);
                mod.classList.toggle('hidden', !hit);
                if (hit) secVisible = true;
            });
            sec.classList.toggle('hidden', !secVisible);
            if (secVisible) { anyVisible = true; if (q) setOpen(sec, true); }
        });
        document.getElementById('perm-empty').classList.toggle('hidden', anyVisible);
    });

    refresh();
})();
</script>
<style>
    .perm-section.section-collapsed .section-body { display: none; }
    .perm-section.section-collapsed .section-arrow { transform: rotate(-90deg); }

    .perm-chip { transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease, transform .1s ease; }
    .perm-chip:not(:has([data-perm]:checked)) { border-color: #e2e8f0; background: #fff; }
    .perm-chip:not(:has([data-perm]:disabled)):hover { box-shadow: 0 2px 8px rgba(15,23,42,.08); transform: translateY(-1px); }
    .perm-chip:has([data-perm]:checked) { border-color: #6ee7b7; background: #ecfdf5; box-shadow: 0 1px 3px rgba(5,150,105,.12); }
    .perm-chip:has([data-perm]:disabled) { opacity: .8; }

    .perm-module { transition: border-color .15s ease, box-shadow .15s ease; }
    .perm-module:hover { border-color: #cbd5e1; }
</style>
@endpush
