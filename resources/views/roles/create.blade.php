<x-app-layout title="Create Role">
    @include('partials.page-header', [
        'title'    => 'Create Role',
        'subtitle' => 'Define a new role and choose exactly what it can access',
        'actions'  => '<a href="'.route('roles.index').'"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5
                                 text-[12px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                           <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                           </svg>
                           Back to Roles
                       </a>',
    ])

    @include('roles._form', ['formAction' => route('roles.store')])
</x-app-layout>
