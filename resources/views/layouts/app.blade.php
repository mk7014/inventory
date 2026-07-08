<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Daraz Manager' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-[#f0f4f1] text-[#17211c] antialiased">

@auth

{{-- Mobile overlay --}}
<div id="sidebar-overlay"
     class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>

<div class="min-h-screen lg:flex">

    {{-- ── Sidebar ───────────────────────────────────────────────── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col
                  -translate-x-full transition-transform duration-300
                  ease-[cubic-bezier(0.4,0,0.2,1)]
                  lg:translate-x-0 lg:z-auto">

        {{-- Sidebar body --}}
        <div class="flex h-full flex-col"
             style="background: linear-gradient(180deg,#0b1610 0%,#152219 60%,#0e1c14 100%);
                    border-right: 1px solid rgba(255,255,255,0.05);
                    box-shadow: 4px 0 24px rgba(0,0,0,0.25);">

            {{-- Brand --}}
            <div class="flex items-center gap-3 px-5 py-5"
                 style="border-bottom: 1px solid rgba(255,255,255,0.07);">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl
                            shadow-lg ring-1 ring-white/10"
                     style="background: linear-gradient(135deg,#34d399,#059669);
                            box-shadow: 0 4px 14px rgba(5,150,105,0.45);">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[13px] font-bold leading-none tracking-wide text-white">
                        Daraz Manager
                    </p>
                    <p class="mt-0.5 text-[10px] font-medium" style="color:rgba(52,211,153,0.6);">
                        Smart IT Solution
                    </p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav id="sidebar-nav" class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">

                @php
                    $dashActive  = request()->routeIs('dashboard');
                    $opsActive   = request()->routeIs('requisitions.*','payments.*','sales.*','returns.*');
                    $invActive   = request()->routeIs('products.*','reports.*');
                    $adminActive = request()->routeIs('accounts.*','users.*','roles.*','balances.*');
                @endphp

                @can('dashboard.view')
                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                   class="nav-single {{ $dashActive ? 'nav-active' : '' }}">
                    <span class="nav-icon-wrap">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2V7z
                                     M13 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2V7z
                                     M3 17a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2z
                                     M13 17a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Dashboard</span>
                </a>
                @endcan

                {{-- My Balance --}}
                <a href="{{ route('balance.mine') }}"
                   class="nav-single {{ request()->routeIs('balance.mine','balance.received','balance.statement') ? 'nav-active' : '' }}">
                    <span class="nav-icon-wrap">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    <span class="nav-label">My Balance</span>
                </a>

                {{-- My Costing (spending breakdown) --}}
                <a href="{{ route('balance.spent') }}"
                   class="nav-single {{ request()->routeIs('balance.spent') ? 'nav-active' : '' }}">
                    <span class="nav-icon-wrap">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M20 12H4m0 0l4-4m-4 4l4 4"/>
                        </svg>
                    </span>
                    <span class="nav-label">My Costing</span>
                </a>

                {{-- Expenses (personal — deducts balance) --}}
                <a href="{{ route('expenses.index') }}"
                   class="nav-single {{ request()->routeIs('expenses.*') ? 'nav-active' : '' }}">
                    <span class="nav-icon-wrap">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Expenses</span>
                </a>

                @canany(['requisitions.view','payments.view','sales.view','returns.view'])
                {{-- ── Operations (accordion) ────────────────────── --}}
                <div class="nav-group {{ $opsActive ? 'open' : '' }}">
                    <button class="nav-parent-btn">
                        <span class="nav-icon-wrap">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                         M9 5a2 2 0 002 2h2a2 2 0 002-2
                                         M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </span>
                        <span class="nav-label">Operations</span>
                        <svg class="nav-arrow h-3.5 w-3.5 flex-shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div class="nav-submenu">
                        <div class="pl-3 pt-1 pb-1 space-y-0.5">
                            @can('requisitions.view')
                            <a href="{{ route('requisitions.index') }}"
                               class="nav-child {{ request()->routeIs('requisitions.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Requisitions
                            </a>
                            @endcan
                            @can('payments.view')
                            <a href="{{ route('payments.index') }}"
                               class="nav-child {{ request()->routeIs('payments.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Payments
                            </a>
                            @endcan
                            @can('sales.view')
                            <a href="{{ route('sales.index') }}"
                               class="nav-child {{ request()->routeIs('sales.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Sales
                            </a>
                            @endcan
                            @can('returns.view')
                            <a href="{{ route('returns.index') }}"
                               class="nav-child {{ request()->routeIs('returns.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                Returns
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                @endcanany

                @canany(['products.view','reports.view'])
                {{-- ── Inventory (accordion) ──────────────────────── --}}
                <div class="nav-group {{ $invActive ? 'open' : '' }}">
                    <button class="nav-parent-btn">
                        <span class="nav-icon-wrap">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </span>
                        <span class="nav-label">Inventory</span>
                        <svg class="nav-arrow h-3.5 w-3.5 flex-shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div class="nav-submenu">
                        <div class="pl-3 pt-1 pb-1 space-y-0.5">
                            @can('products.view')
                            <a href="{{ route('products.index') }}"
                               class="nav-child {{ request()->routeIs('products.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                Products & Stock
                            </a>
                            @endcan
                            @can('reports.view')
                            <a href="{{ route('reports.index') }}"
                               class="nav-child {{ request()->routeIs('reports.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Reports
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- ── Admin Panel (accordion) ─────────────────────── --}}
                @canany(['accounts.view','users.view','roles.view','balances.view'])
                <div class="nav-group {{ $adminActive ? 'open' : '' }}">
                    <button class="nav-parent-btn">
                        <span class="nav-icon-wrap">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </span>
                        <span class="nav-label">Admin Panel</span>
                        <svg class="nav-arrow h-3.5 w-3.5 flex-shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div class="nav-submenu">
                        <div class="pl-3 pt-1 pb-1 space-y-0.5">
                            @can('accounts.view')
                            <a href="{{ route('accounts.index') }}"
                               class="nav-child {{ request()->routeIs('accounts.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Daraz Accounts
                            </a>
                            @endcan
                            @can('users.view')
                            <a href="{{ route('users.index') }}"
                               class="nav-child {{ request()->routeIs('users.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Users
                            </a>
                            @endcan
                            @can('roles.view')
                            <a href="{{ route('roles.index') }}"
                               class="nav-child {{ request()->routeIs('roles.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Roles &amp; Permissions
                            </a>
                            @endcan
                            @can('balances.view')
                            <a href="{{ route('balances.index') }}"
                               class="nav-child {{ request()->routeIs('balances.*') ? 'nav-child-active' : '' }}">
                                <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Employee Balances
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                @endcanany

            </nav>

            {{-- User profile --}}
            @php $me = auth()->user(); @endphp
            <div class="px-4 py-4" style="border-top: 1px solid rgba(255,255,255,0.07);">
                <div class="flex items-center gap-3">
                    <a href="{{ route('profile.edit') }}" title="My Profile"
                       class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1.5 transition-all duration-200
                              {{ request()->routeIs('profile.*') ? 'bg-white/10' : '' }}"
                       onmouseover="this.style.background='rgba(255,255,255,0.07)'"
                       onmouseout="this.style.background='{{ request()->routeIs('profile.*') ? 'rgba(255,255,255,0.1)' : '' }}'">
                        <div class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full ring-2"
                             style="ring-color: rgba(52,211,153,0.25);">
                            @if($me->avatarUrl())
                                <img src="{{ $me->avatarUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-xs font-bold text-white"
                                     style="background: linear-gradient(135deg,#34d399,#059669);">
                                    {{ $me->initials() }}
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[12px] font-semibold text-white">{{ $me->name }}</p>
                            <p class="text-[10px] font-medium" style="color:rgba(52,211,153,0.55);">
                                {{ $me->role?->name ?? 'No role' }}
                            </p>
                        </div>
                    </a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button title="Sign out"
                                class="rounded-lg p-1.5 transition-all duration-200"
                                style="color:rgba(255,255,255,0.35);"
                                onmouseover="this.style.color='white';this.style.background='rgba(255,255,255,0.09)'"
                                onmouseout="this.style.color='rgba(255,255,255,0.35)';this.style.background=''">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

        </div>{{-- /sidebar body --}}
    </aside>

    {{-- ── Main content ────────────────────────────────────────────── --}}
    <main class="flex min-h-screen flex-1 flex-col lg:ml-64">

        {{-- Mobile top bar --}}
        <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-slate-200/70 bg-white/80
                        px-4 py-3 backdrop-blur-lg shadow-sm lg:hidden">
            <button id="sidebar-toggle"
                    class="rounded-lg p-2 text-slate-500 transition-all duration-200
                           hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="text-sm font-semibold text-[#17211c]">{{ $title ?? 'Daraz Manager' }}</span>
        </header>

        <div class="page-fade mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @include('partials.flash')
            {{ $slot }}
        </div>

    </main>

</div>{{-- /lg:flex --}}

@else
    {{ $slot }}
@endauth

@stack('scripts')
</body>
</html>
