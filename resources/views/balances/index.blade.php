<x-app-layout title="Employee Balances">
    @include('partials.page-header', [
        'title'    => 'Employee Balances',
        'subtitle' => 'Money paid to each employee against their requisitions',
    ])

    {{-- ── Total credited card ─────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="stat-card rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Total Balance</p>
                    <p class="mt-2 text-2xl font-bold text-[#17211c]">৳ {{ number_format($totalBalance, 2) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Across all employees</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Balances table ──────────────────────────────────────────── --}}
    <section class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-[13px] font-bold text-[#17211c]">Employee Accounts</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ $users->total() }} employees</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-160 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">Employee</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="tbl-row">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full
                                                bg-linear-to-br from-emerald-400 to-emerald-600
                                                text-[10px] font-bold text-white">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <span class="font-medium text-slate-800">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $user->email }}</td>
                            <td class="px-5 py-3">@include('partials.status', ['status' => $user->status])</td>
                            <td class="px-5 py-3 text-right font-bold {{ (float) $user->balance < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                ৳ {{ number_format($user->balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="border-t border-slate-100 px-5 py-3">{{ $users->links() }}</div>
        @endif
    </section>
</x-app-layout>
