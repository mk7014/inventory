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
                                                        bg-linear-to-br from-violet-400 to-violet-600
                                                        text-[10px] font-bold text-white">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <input name="name" value="{{ $user->name }}"
                                                   class="ppp-field">
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
                                        <button class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5
                                                       text-[11px] font-semibold text-violet-700 transition
                                                       hover:bg-violet-600 hover:text-white hover:border-transparent">
                                            Save
                                        </button>
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
</x-app-layout>
