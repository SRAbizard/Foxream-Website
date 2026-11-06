{{-- Language and light/dark on the auth pages (login, register, password
     reset), top right of the form card. The theme choice is also saved
     for the public site, so both stay the same. --}}
<div class="site-auth-tools">
    @include('filament.partials.locale-switcher')

    <button type="button" class="site-theme-toggle"
        x-data
        x-on:click="const t = $store.theme === 'dark' ? 'light' : 'dark'; try { localStorage.setItem('theme', t) } catch (e) {} $dispatch('theme-changed', t)"
        x-bind:aria-label="$store.theme === 'dark' ? @js(__('Light mode')) : @js(__('Dark mode'))"
        x-bind:title="$store.theme === 'dark' ? @js(__('Light mode')) : @js(__('Dark mode'))">
        <svg x-show="$store.theme !== 'dark'" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        <svg x-show="$store.theme === 'dark'" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>
</div>
