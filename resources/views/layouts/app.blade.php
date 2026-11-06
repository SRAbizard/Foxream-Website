<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light" data-intro-desktop="{{ \App\Support\Brand::introVideo('desktop') }}" data-intro-mobile="{{ \App\Support\Brand::introVideo('mobile') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', __('Japan Career, Recruitment & Internship Platform'))">
    {{-- Tab icon: the fox. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Theme: washi (light) is the default; apply a saved "dark" choice
         BEFORE paint to avoid a flash. .pj-js opts into JS-driven reveals. --}}
    <script>
        (function () {
            var html = document.documentElement;
            html.classList.add('pj-js');
            // One theme for the website and both Filament panels: Filament
            // stores light|dark|system under "theme"; "pj-theme" is the
            // site's older key, used only when "theme" was never set.
            window.pjIsDark = function () {
                var t = null;
                try { t = localStorage.getItem('theme') || localStorage.getItem('pj-theme'); } catch (e) {}
                if (t === 'system') return matchMedia('(prefers-color-scheme: dark)').matches;
                return t === 'dark';
            };
            try {
                if (window.pjIsDark()) { html.classList.remove('light'); html.classList.add('dark'); }
                // First visit this session → black cover until resources/js/intro.js plays the intro video;
                // not with reduced motion, data saver, or no video.
                var saveData = navigator.connection && navigator.connection.saveData;
                if (! sessionStorage.getItem('pj-intro-seen') && ! saveData && html.dataset.introDesktop
                    && ! matchMedia('(prefers-reduced-motion: reduce)').matches) html.classList.add('pj-intro');
            } catch (e) {}
        })();
    </script>

    {{-- hreflang alternates for SEO multilingual --}}
    @foreach(config('site.locales') as $code => $meta)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ url()->current() }}?lang={{ $code }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <title>@yield('title', __(':brand'))</title>

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ \App\Support\Brand::name() }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:title" content="@yield('og_title', __(':brand'))">
    <meta property="og:description" content="@yield('og_description', __('Japan Career, Recruitment & Internship Platform'))">
    <meta property="og:image" content="{{ asset('images/brand/og.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">

    @stack('head')

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire scripts so wire:navigate can swap pages without a full
         reload. The audio player uses @persist below so it survives the swap. --}}
    @livewireStyles
</head>
<body class="min-h-screen antialiased font-sans">

@php
    $academyUrl = \App\Support\SiteSettings::academyUrl();
    $navLinks = [
        ['route' => 'event.index',   'label' => __('Event'),   'active' => 'event.*'],
        ['route' => 'blog.index',    'label' => __('Blog'),    'active' => 'blog.*'],
        ['route' => 'gallery.index', 'label' => __('Gallery'), 'active' => 'gallery.*'],
    ];
@endphp

<header class="pj-header">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <nav class="pj-navbar" aria-label="{{ __('Main') }}">
            <a href="{{ url('/') }}" wire:navigate class="flex items-center shrink-0">
                {{-- The fox and the name; the name is white on the dark theme. --}}
                <img src="{{ \App\Support\Brand::logo('horizontal', 'light') }}" alt="{{ \App\Support\Brand::name() }}" class="h-9 sm:h-10 w-auto select-none dark:hidden" draggable="false">
                <img src="{{ \App\Support\Brand::logo('horizontal', 'dark') }}" alt="{{ \App\Support\Brand::name() }}" class="h-9 sm:h-10 w-auto select-none hidden dark:block" draggable="false">
            </a>

            <div class="hidden lg:flex items-center gap-1">
                <a href="{{ url('/') }}" wire:navigate class="pj-navlink {{ request()->routeIs('home') ? 'is-active' : '' }}">{{ __('Home') }}</a>

                {{-- Programs dropdown — job portal + (external) learning platform --}}
                <div class="relative group">
                    <button type="button" class="pj-navlink inline-flex items-center gap-1 {{ request()->routeIs('job.*', 'services') ? 'is-active' : '' }}">
                        {{ __('Programs') }}
                        <svg class="h-3 w-3 opacity-60 transition group-hover:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="absolute left-0 top-full pt-2 hidden group-hover:block group-focus-within:block min-w-[250px] z-30">
                        <div class="pj-menu">
                            <a href="{{ route('job.index') }}" wire:navigate>
                                <span class="block font-bold">{{ __('Job Vacancies') }}</span>
                                <span class="block text-xs opacity-70">{{ __('Verified jobs from partner companies') }}</span>
                            </a>
                            @if($academyUrl)
                                <a href="{{ $academyUrl }}" target="_blank" rel="noopener">
                                    <span class="flex items-center gap-1.5 font-bold">
                                        {{ __('E-Learning') }}
                                        <svg class="h-3 w-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </span>
                                    <span class="block text-xs opacity-70">{{ __('Learn Japanese at :learning') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @foreach($navLinks as $link)
                    <a href="{{ route($link['route']) }}" wire:navigate class="pj-navlink {{ request()->routeIs($link['active']) ? 'is-active' : '' }}">{{ $link['label'] }}</a>
                @endforeach

                {{-- Perusahaan dropdown — About + Contact --}}
                <div class="relative group">
                    <button type="button" class="pj-navlink inline-flex items-center gap-1 {{ request()->routeIs('about', 'contact') ? 'is-active' : '' }}">
                        {{ __('Company') }}
                        <svg class="h-3 w-3 opacity-60 transition group-hover:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="absolute left-0 top-full pt-2 hidden group-hover:block group-focus-within:block min-w-[200px] z-30">
                        <div class="pj-menu">
                            <a href="{{ route('about') }}" wire:navigate class="font-semibold">{{ __('About Us') }}</a>
                            <a href="{{ route('contact') }}" wire:navigate class="font-semibold">{{ __('Contact') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                {{-- Theme toggle (sun/moon) --}}
                <button type="button" data-theme-toggle class="pj-iconbtn hidden! sm:inline-flex!"
                        title="{{ __('Toggle dark / light mode') }}" aria-label="{{ __('Toggle dark / light mode') }}">
                    <svg class="theme-icon-sun h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="theme-icon-moon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>

                {{-- Language switcher --}}
                <div class="relative group hidden sm:block">
                    <button type="button" class="pj-iconbtn" aria-label="{{ __('Language') }}">
                        <x-flag :code="app()->getLocale()" />
                        <span class="uppercase">{{ app()->getLocale() }}</span>
                    </button>
                    <div class="absolute right-0 top-full pt-2 hidden group-hover:block group-focus-within:block min-w-[190px] z-30">
                        <div class="pj-menu">
                            @foreach(config('site.locales') as $code => $meta)
                                <a href="{{ route('locale.switch', $code) }}" class="flex items-center gap-2 text-sm {{ app()->getLocale() === $code ? 'font-bold' : '' }}">
                                    <x-flag :code="$code" /><span>{{ $meta['native'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                @auth
                    @php($user = auth()->user())
                    @if ($user->hasAnyRole(['superadmin', 'admin']))
                        <a href="/admin" class="pj-navlink hidden sm:inline-flex">{{ __('Admin Panel') }}</a>
                    @else
                        <a href="/dashboard" class="pj-navlink hidden sm:inline-flex">{{ __('Student Dashboard') }}</a>
                    @endif
                    <form method="POST" action="/dashboard/logout" class="hidden sm:inline-flex">
                        @csrf
                        <button type="submit" class="pj-navlink">{{ __('Logout') }}</button>
                    </form>
                @else
                    <a href="/dashboard/login" class="pj-navlink hidden sm:inline-flex">{{ __('Login') }}</a>
                    <a href="/dashboard/register" class="pj-btn is-red py-2.5! px-4! text-sm whitespace-nowrap">{{ __('Start Mission') }}</a>
                @endauth

                {{-- Mobile menu --}}
                <details class="lg:hidden relative">
                    <summary class="list-none pj-iconbtn" aria-label="{{ __('Menu') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </summary>
                    <div class="absolute right-0 mt-2 min-w-[240px] pj-menu flex flex-col text-sm z-40">
                        <a href="{{ url('/') }}" wire:navigate>{{ __('Home') }}</a>
                        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider opacity-60 font-bold">{{ __('Programs') }}</p>
                        <a href="{{ route('job.index') }}" wire:navigate>{{ __('Job Vacancies') }}</a>
                        @if($academyUrl)
                            <a href="{{ $academyUrl }}" target="_blank" rel="noopener">{{ __('E-Learning') }} ↗</a>
                        @endif
                        @foreach($navLinks as $link)
                            <a href="{{ route($link['route']) }}" wire:navigate>{{ $link['label'] }}</a>
                        @endforeach
                        <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider opacity-60 font-bold">{{ __('Company') }}</p>
                        <a href="{{ route('about') }}" wire:navigate>{{ __('About Us') }}</a>
                        <a href="{{ route('contact') }}" wire:navigate>{{ __('Contact') }}</a>
                        @guest
                            <a href="/dashboard/login" class="sm:hidden font-bold">{{ __('Login') }}</a>
                        @endguest
                        <div class="sm:hidden mt-2 pt-2 border-t-2 border-dashed border-current/20 flex items-center gap-1.5 px-1">
                            @foreach(config('site.locales') as $code => $meta)
                                <a href="{{ route('locale.switch', $code) }}" class="pj-iconbtn {{ app()->getLocale() === $code ? 'bg-[var(--pj-sakura)]!' : '' }}" aria-label="{{ $meta['native'] }}">
                                    <x-flag :code="$code" /> <span class="uppercase">{{ $code }}</span>
                                </a>
                            @endforeach
                            <button type="button" data-theme-toggle class="pj-iconbtn ml-auto" aria-label="{{ __('Toggle dark / light mode') }}">
                                <svg class="theme-icon-sun h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <svg class="theme-icon-moon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            </button>
                        </div>
                    </div>
                </details>
            </div>
        </nav>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="mt-24">
    {{-- "Ready to depart?" CTA band --}}
    <section class="pj-footer-cta">
        <div class="pj-scallop" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-7xl px-6 py-14 lg:py-20 grid gap-8 lg:grid-cols-[1.3fr_.7fr] items-center">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] font-bold opacity-80">{{ __('Free consultation') }}</p>
                <h2 class="mt-3 font-display text-4xl sm:text-6xl lg:text-7xl leading-none">{{ __('Ready to depart?') }}</h2>
                <p class="mt-4 max-w-xl text-lg opacity-90">{{ __('Create a free account, complete your profile, and apply for your first job today. Our team is ready to help on WhatsApp.') }}</p>
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <a href="/dashboard/register" class="pj-btn is-gold">{{ __('Start your mission') }} →</a>
                    <a href="https://wa.me/{{ \App\Support\SiteSettings::contact('whatsapp') }}" target="_blank" rel="noopener" class="pj-btn">
                        {{ __('Talk to us on WhatsApp') }}
                    </a>
                </div>
            </div>
            <svg class="w-full max-w-[300px] justify-self-start lg:justify-self-end" viewBox="0 0 300 220" aria-hidden="true">
                <circle cx="150" cy="96" r="70" fill="#FFFAF2" stroke="#1D1730" stroke-width="3"/>
                <path d="M0 220 L100 110 Q120 88 136 86 L164 86 Q180 88 200 110 L300 220 Z" fill="#1D1730"/>
                <path d="M118 100 Q128 88 138 86 L162 86 Q172 88 182 100 L170 110 L160 100 L150 114 L140 100 L130 110 Z" fill="#FFFAF2"/>
            </svg>
        </div>
    </section>

    <div class="pj-footer">
        <div class="mx-auto max-w-7xl px-6 py-14 grid gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div>
                {{-- The footer is always dark: the logo with the white name. --}}
                <img src="{{ \App\Support\Brand::logo('stacked', 'dark') }}" alt="{{ \App\Support\Brand::name() }}" class="h-32 w-auto mb-4 select-none" draggable="false">
                <p class="text-sm leading-relaxed opacity-80">{{ __('Official job matching for Indonesian workers in Japan, from your application to your first day at work.') }}</p>
                <div class="mt-5 flex items-center gap-3">
                    <a href="{{ \App\Support\SiteSettings::social('instagram') }}" target="_blank" rel="noopener" aria-label="Instagram" class="opacity-80 hover:opacity-100 transition">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M7.75 2A5.75 5.75 0 002 7.75v8.5A5.75 5.75 0 007.75 22h8.5A5.75 5.75 0 0022 16.25v-8.5A5.75 5.75 0 0016.25 2h-8.5zm0 1.5h8.5a4.25 4.25 0 014.25 4.25v8.5a4.25 4.25 0 01-4.25 4.25h-8.5A4.25 4.25 0 013.5 16.25v-8.5A4.25 4.25 0 017.75 3.5zM17 6a1 1 0 100 2 1 1 0 000-2zm-5 1.5a4.5 4.5 0 100 9 4.5 4.5 0 000-9zm0 1.5a3 3 0 110 6 3 3 0 010-6z"/></svg>
                    </a>
                    <a href="{{ \App\Support\SiteSettings::social('facebook') }}" target="_blank" rel="noopener" aria-label="Facebook" class="opacity-80 hover:opacity-100 transition">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M13.5 21.95V13.5h2.85l.43-3.3h-3.28V8.1c0-.95.27-1.6 1.64-1.6H17V3.55c-.3-.04-1.34-.13-2.55-.13-2.52 0-4.25 1.54-4.25 4.37V10.2H7.35v3.3h2.85v8.45h3.3z"/></svg>
                    </a>
                    <a href="{{ \App\Support\SiteSettings::social('tiktok') }}" target="_blank" rel="noopener" aria-label="TikTok" class="opacity-80 hover:opacity-100 transition">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.5 8.2a6.7 6.7 0 01-4.2-1.4v6.7a5.4 5.4 0 11-5.4-5.4c.4 0 .8.04 1.1.12v2.7a2.7 2.7 0 102 2.58V2h2.6a4.1 4.1 0 003.9 3.6v2.6z"/></svg>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="font-display text-lg text-white mb-4">{{ __('Quick Links') }}</h4>
                <ul class="space-y-2 text-sm opacity-85">
                    <li><a href="{{ url('/') }}" wire:navigate>{{ __('Home') }}</a></li>
                    <li><a href="{{ route('job.index') }}" wire:navigate>{{ __('Job Vacancies') }}</a></li>
                    <li><a href="{{ route('services') }}" wire:navigate>{{ __('Services') }}</a></li>
                    <li><a href="{{ route('fees') }}" wire:navigate>{{ __('Programme Fees') }}</a></li>
                    @if($academyUrl)
                        <li><a href="{{ $academyUrl }}" target="_blank" rel="noopener">{{ __('E-Learning') }} ↗</a></li>
                    @endif
                    <li><a href="{{ route('event.index') }}" wire:navigate>{{ __('Event') }}</a></li>
                    <li><a href="{{ route('blog.index') }}" wire:navigate>{{ __('Blog') }}</a></li>
                    <li><a href="{{ route('gallery.index') }}" wire:navigate>{{ __('Gallery') }}</a></li>
                    <li><a href="{{ route('about') }}" wire:navigate>{{ __('About Us') }}</a></li>
                    <li><a href="{{ route('contact') }}" wire:navigate>{{ __('Contact') }}</a></li>
                </ul>
            </div>

            <div>
                <h4 class="font-display text-lg text-white mb-4">{{ __('Visa routes') }}</h4>
                <ul class="space-y-2 text-sm opacity-85">
                    @foreach(\App\Models\VisaCategory::orderBy('sort_order')->get() as $visa)
                        <li><a href="{{ route('job.index', ['visa' => $visa->slug]) }}" wire:navigate>{{ $visa->t('name') }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4 class="font-display text-lg text-white mb-4">{{ __('Contact') }}</h4>
                <ul class="space-y-3 text-sm opacity-85">
                    <li><a href="mailto:{{ \App\Support\SiteSettings::contact('email') }}">{{ \App\Support\SiteSettings::contact('email') }}</a></li>
                    <li><a href="tel:{{ str_replace(' ', '', \App\Support\SiteSettings::contact('phone') ?? '') }}">{{ \App\Support\SiteSettings::contact('phone') }}</a></li>
                    @foreach(\App\Support\SiteSettings::offices() as $office)
                        <li>
                            <a href="{{ $office['maps_url'] }}" target="_blank" rel="noopener noreferrer" title="{{ __('Open in Google Maps') }}" class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 shrink-0 text-[#F1AF2E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ trim(($office['city'] ?? '').', '.($office['country'] ?? ''), ', ') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto max-w-7xl px-6 pt-5 pb-24 text-xs opacity-70">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                    <p>&copy; {{ date('Y') }} {{ \App\Support\Brand::company() }}. {{ __('All rights reserved.') }}</p>
                    <nav class="flex flex-wrap gap-x-4 gap-y-1" aria-label="{{ __('Legal') }}">
                        <a href="{{ route('legal.privacy') }}" wire:navigate>{{ __('Privacy Policy') }}</a>
                        <a href="{{ route('legal.terms') }}" wire:navigate>{{ __('Terms & Conditions') }}</a>
                        <a href="{{ route('fees') }}#waspada" wire:navigate>{{ __('Beware of fraud') }}</a>
                    </nav>
                </div>
                @if($songCredit = \App\Support\SiteSettings::themeAudioCredit())
                    <p class="mt-1 opacity-70">{{ __('Music') }}: {{ $songCredit }}</p>
                @endif
            </div>
        </div>
    </div>
</footer>

<x-whatsapp-float />

@persist('audio-player')
    <x-audio-player />
@endpersist

@livewireScripts

{{-- data-navigate-once: wire:navigate re-runs body scripts on every page
     swap, which would stack a second click listener (one click → two
     toggles → no change). Everything below re-binds itself on navigation. --}}
<script data-navigate-once>
    (function () {
        // ─── Theme persistence across wire:navigate ──────────────────
        // Livewire copies the new page's <html> attributes (class="light"
        // from the server), so re-apply the saved theme right after the
        // swap — before paint — and again once navigation finishes.
        function pjApplyTheme () {
            var html = document.documentElement, dark = window.pjIsDark ? window.pjIsDark() : false;
            html.classList.toggle('dark', dark);
            html.classList.toggle('light', ! dark);
            html.classList.add('pj-js');
        }
        document.addEventListener('livewire:navigating', function (e) {
            if (e.detail && typeof e.detail.onSwap === 'function') e.detail.onSwap(pjApplyTheme);
        });
        document.addEventListener('livewire:navigated', pjApplyTheme);

        // ─── Theme toggle ────────────────────────────────────────────
        // Bound by delegation so it keeps working after wire:navigate swaps.
        document.addEventListener('click', function (e) {
            if (! e.target.closest('[data-theme-toggle]')) return;
            var html = document.documentElement;
            var goingDark = ! html.classList.contains('dark');
            html.classList.toggle('dark', goingDark);
            html.classList.toggle('light', ! goingDark);
            // Write both keys so the admin/student dashboards follow too.
            try {
                localStorage.setItem('theme', goingDark ? 'dark' : 'light');
                localStorage.setItem('pj-theme', goingDark ? 'dark' : 'light');
            } catch (err) {}
        });

        // ─── Reveal-on-scroll (IntersectionObserver) ─────────────────
        function pjBindReveal () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            document.querySelectorAll('.reveal:not(.is-visible)').forEach(function (el) { io.observe(el); });
        }
        pjBindReveal();

        // ─── Lite YouTube embed: click poster to load iframe ─────────
        function pjBindLiteYT () {
            document.querySelectorAll('[data-pj-lite-yt]:not([data-pj-bound])').forEach(function (el) {
                el.setAttribute('data-pj-bound', '1');
                el.addEventListener('click', function () {
                    var url = el.dataset.embedUrl;
                    if (!url) return;
                    var iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.className = 'absolute inset-0 h-full w-full';
                    iframe.setAttribute('frameborder', '0');
                    iframe.setAttribute('allowfullscreen', '');
                    iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                    iframe.setAttribute('title', 'YouTube video');
                    el.replaceWith(iframe);
                });
            });
        }
        pjBindLiteYT();

        document.addEventListener('livewire:navigated', function () {
            pjBindReveal();
            pjBindLiteYT();
        });
    })();
</script>

</body>
</html>
