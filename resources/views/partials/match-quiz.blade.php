{{-- Match quiz block. Needs $quiz (App\Support\MatchingData::quiz()); the
     page must also print MatchingData::json() as #site-home-data inside a
     [data-site-home] wrapper — behaviour lives in resources/js/home.js. --}}
<div class="site-head">
    <span class="site-sticker is-pink">{{ __('Mission 01 · 1 minute') }}</span>
    <h2>{{ __('Check your match') }}</h2>
    <p class="site-lede">{{ __('Answer 4 questions. We will show the visa route that fits you best and matching jobs.') }}</p>
</div>
<div class="site-quiz">
    <div class="site-game site-card">
        <div class="site-game-bar"><span data-site-q-label>{{ __('QUESTION') }} 1 / 4</span><div class="site-xp" aria-hidden="true"><i data-site-xp></i></div><span data-site-q-xp>0 XP</span></div>
        <div class="site-game-body" data-site-game aria-live="polite">
            <span class="site-q-num">{{ __('QUESTION') }} 1</span>
            <p class="site-q-title">{{ $quiz[0]['q'] }}</p>
            <div class="site-opts">
                @foreach($quiz[0]['o'] as $o)
                    <button type="button" class="site-opt">{{ $o[0] }}@if($o[1])<small>{{ $o[1] }}</small>@endif</button>
                @endforeach
            </div>
        </div>
    </div>
    <aside class="grid gap-4">
        <div class="site-side-card site-card is-tilt">
            <h3>{{ __('Three routes to Japan') }}</h3>
            <ul>
                <li><b>Tokutei Ginou (SSW)</b>: {{ __('needs Japanese N4/JFT A2 and a skills test.') }}</li>
                <li><b>{{ __('Internship (Ginou Jisshu)') }}</b>: {{ __('suits beginners; you are trained from the start.') }}</li>
                <li><b>{{ __('Engineer (Gijinkoku)') }}</b>: {{ __('for D3/S1 graduates in engineering or IT.') }}</li>
            </ul>
            <a href="{{ route('services') }}" wire:navigate class="font-bold underline underline-offset-4">{{ __('See each visa route in detail') }} →</a>
        </div>
        <p class="site-disclaimer">{{ __('This check is a simulation for initial guidance. The final decision follows each job’s requirements and a consultation with our team.') }}</p>
    </aside>
</div>
