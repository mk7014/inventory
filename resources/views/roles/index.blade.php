<x-app-layout title="Roles & Permissions">
    @include('partials.page-header', [
        'title'    => 'Roles & Permissions',
        'subtitle' => 'Create roles and control exactly what each one can access',
        'actions'  => auth()->user()->can('roles.create')
            ? '<a href="'.route('roles.create').'"
                  class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-[12px] font-semibold
                         text-white shadow-sm transition hover:bg-emerald-700">
                   <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                   </svg>
                   New Role
               </a>'
            : '',
    ])

    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">All Roles</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $roles->total() }} role(s)</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-200 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-center">Permissions</th>
                        <th class="px-5 py-3 text-center">Users</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roles as $role)
                        <tr class="tbl-row align-middle">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl
                                                {{ $role->slug === 'admin' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-[#17211c]">{{ $role->name }}</p>
                                        @if($role->is_system)
                                            <span class="text-[10px] font-medium text-slate-400">System role</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-500 max-w-xs">{{ $role->description ?: '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">
                                    {{ $role->slug === 'admin' ? 'All' : $role->permissions_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600">{{ $role->users_count }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium
                                             {{ $role->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $role->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ ucfirst($role->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @can('roles.update')
                                        <a href="{{ route('roles.edit', $role) }}"
                                           class="rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('roles.delete')
                                        @unless($role->is_system)
                                            <form method="post" action="{{ route('roles.destroy', $role) }}"
                                                  onsubmit="return confirm('Delete the “{{ $role->name }}” role? This cannot be undone.');">
                                                @csrf @method('delete')
                                                <button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-600 hover:text-white hover:border-transparent">
                                                    Delete
                                                </button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">No roles yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($roles->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $roles->links() }}</div>
        @endif
    </section>
</x-app-layout>
