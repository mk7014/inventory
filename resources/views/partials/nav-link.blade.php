@php($active = request()->routeIs($route) || request()->routeIs(explode('.', $route)[0].'.*'))
<a href="{{ route($route) }}" class="rounded px-3 py-2 font-medium {{ $active ? 'bg-white text-[#17211c]' : 'text-[#dbe4dd] hover:bg-white/10 hover:text-white' }}">
    {{ $label }}
</a>
