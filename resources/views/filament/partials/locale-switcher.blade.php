{{-- Language dropdown in the panel topbar (admin + student). --}}
@php
    $locales = config('site.locales', []);
    $current = app()->getLocale();
@endphp

<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button type="button" class="site-locale-trigger" aria-label="{{ __('Language') }}" title="{{ __('Language') }}">
            <x-flag :code="$current" />
            <span class="code">{{ strtoupper($current) }}</span>
            <svg class="h-3 w-3 opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </button>
    </x-slot>

    <x-filament::dropdown.header icon="heroicon-o-language">{{ __('Language') }}</x-filament::dropdown.header>

    <x-filament::dropdown.list>
        @foreach($locales as $code => $l)
            <x-filament::dropdown.list.item tag="a" :href="route('locale.switch', $code)"
                :color="$code === $current ? 'primary' : 'gray'"
                :icon="$code === $current ? 'heroicon-m-check' : null">
                <span class="site-locale-item"><x-flag :code="$code" /> {{ $l['native'] }}</span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
