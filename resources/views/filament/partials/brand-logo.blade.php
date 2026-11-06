{{-- Panel brand logo: the fox and the name in the topbar, the stacked
     logo on the login/register pages; the name is white on the dark theme. --}}
@php $kind = str_contains(request()->route()?->getName() ?? '', '.auth.') ? 'stacked' : 'horizontal'; @endphp
<img src="{{ \App\Support\Brand::logo($kind, 'light') }}" alt="{{ \App\Support\Brand::name() }}" class="site-brand-{{ $kind }} dark:hidden" draggable="false">
<img src="{{ \App\Support\Brand::logo($kind, 'dark') }}" alt="{{ \App\Support\Brand::name() }}" class="site-brand-{{ $kind }} hidden dark:block" draggable="false">
