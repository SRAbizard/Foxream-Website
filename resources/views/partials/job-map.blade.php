{{-- Region job map block. Needs $regions (App\Support\MatchingData::regions()); see match-quiz.blade.php for the page wiring. --}}
<div class="pj-head">
    <h2>{{ __('Where in Japan do you want to work?') }}</h2>
    <p class="pj-lede">{{ __('Tap a region on the map to see its openings.') }}</p>
</div>
<div class="pj-map">
    <div class="pj-map-box pj-card">
        <svg class="pj-japan" viewBox="0 0 400 440" role="group" aria-label="{{ __('Job map of Japan') }}">
            <path class="land" d="M292 34 Q318 18 344 26 Q372 30 384 54 Q378 80 356 92 Q338 108 314 104 Q294 96 286 74 Q282 50 292 34 Z"/>
            <path class="land" d="M284 120 Q300 126 310 146 Q314 176 304 204 Q296 232 280 252 Q262 272 236 284 Q206 296 176 310 Q146 324 116 334 Q96 340 84 334 Q78 324 92 316 Q118 304 146 292 Q178 278 206 264 Q232 250 248 228 Q262 204 266 174 Q268 144 284 120 Z"/>
            <path class="land" d="M132 344 Q158 336 184 336 Q198 342 192 356 Q172 366 146 366 Q130 360 132 344 Z"/>
            <path class="land" d="M58 342 Q78 340 92 350 Q100 370 92 392 Q80 412 64 410 Q48 402 44 382 Q42 358 58 342 Z"/>
            <path class="land" d="M14 424 q8 -6 14 0 q-6 8 -14 0 Z"/>
            @foreach($regions as $r)
                @php $left = $r['x'] < 120; @endphp
                <g class="pj-pin {{ $r['count'] ? '' : 'is-empty' }}" tabindex="0" role="button" data-pj-pin="{{ $r['key'] }}"
                   aria-label="{{ $r['name'] }}: {{ $r['count'] }} {{ __('jobs in this region') }}">
                    <circle class="pulse" cx="{{ $r['x'] }}" cy="{{ $r['y'] }}" r="9"/>
                    <circle class="dot" cx="{{ $r['x'] }}" cy="{{ $r['y'] }}" r="8"/>
                    <text x="{{ $left ? $r['x'] + 14 : $r['x'] - 14 }}" y="{{ $r['y'] + 4 }}" text-anchor="{{ $left ? 'start' : 'end' }}">{{ $r['name'] }}{{ $r['count'] ? ' · '.$r['count'] : '' }}</text>
                </g>
            @endforeach
        </svg>
    </div>
    <div class="pj-city pj-card" data-pj-city aria-live="polite">
        <p class="pj-lede">{{ __('Tap a region on the map to see its openings.') }}</p>
    </div>
</div>
