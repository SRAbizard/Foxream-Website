@props(['compact' => false])

{{-- Anti-fraud warning with the official channels. Points are edited in
     Admin → Legal & Fees; see App\Support\Legal. --}}
@php
    $points   = \App\Support\Legal::fraudPoints();
    $channels = \App\Support\Legal::officialChannels();
@endphp

<aside {{ $attributes->class(['pj-fraud', 'is-compact' => $compact]) }} id="waspada">
    <div class="pj-fraud-head">
        <span class="pj-fraud-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 4.6-3.3 8.3-8 9-4.7-.7-8-4.4-8-9V6l8-3z"/><path d="M12 8v5M12 16.5v.01"/></svg>
        </span>
        <div>
            <p class="pj-fraud-title">{{ __('Beware of fraud') }} <span class="jp">詐欺に注意</span></p>
            @unless($compact)
                <p class="pj-fraud-sub">{{ __('Protect yourself from anyone falsely using the :brand name.') }}</p>
            @endunless
        </div>
    </div>

    <ul class="pj-fraud-list">
        @foreach($compact ? array_slice($points, 0, 3) : $points as $point)
            <li>{{ $point }}</li>
        @endforeach
    </ul>

    @if($channels)
        <div class="pj-fraud-channels">
            <span>{{ __('Official channels') }}:</span>
            @foreach($channels as $c)
                <a href="{{ $c['url'] }}" @if(! str_starts_with((string) $c['url'], url('/'))) target="_blank" rel="noopener" @endif><b>{{ $c['label'] }}</b> {{ $c['value'] }}</a>
            @endforeach
        </div>
    @endif

    @if($compact)
        <a href="{{ route('fees') }}#waspada" wire:navigate class="pj-fraud-more">{{ __('Read more & programme fees') }} →</a>
    @endif
</aside>
