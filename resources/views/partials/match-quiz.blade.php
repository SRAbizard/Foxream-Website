{{-- Match quiz block. Needs $quiz (App\Support\MatchingData::quiz()); the
     page must also print MatchingData::json() as #pj-home-data inside a
     [data-pj-home] wrapper — behaviour lives in resources/js/home.js. --}}
<div class="pj-head">
    <span class="pj-sticker is-pink">{{ __('Mission 01 · 1 minute') }}</span>
    <h2>{{ __('Check your match') }}</h2>
    <p class="pj-lede">{{ __('Answer 4 questions. We will show the visa route that fits you best and matching jobs.') }}</p>
</div>
<div class="pj-quiz">
    <div class="pj-game pj-card">
        <div class="pj-game-bar"><span data-pj-q-label>{{ __('QUESTION') }} 1 / 4</span><div class="pj-xp" aria-hidden="true"><i data-pj-xp></i></div><span data-pj-q-xp>0 XP</span></div>
        <div class="pj-game-body" data-pj-game aria-live="polite">
            <span class="pj-q-num">{{ __('QUESTION') }} 1</span>
            <p class="pj-q-title">{{ $quiz[0]['q'] }}</p>
            <div class="pj-opts">
                @foreach($quiz[0]['o'] as $o)
                    <button type="button" class="pj-opt">{{ $o[0] }}@if($o[1])<small>{{ $o[1] }}</small>@endif</button>
                @endforeach
            </div>
        </div>
    </div>
    <aside class="grid gap-4">
        <div class="pj-side-card pj-card is-tilt">
            <h3>{{ __('Three routes to Japan') }}</h3>
            <ul>
                <li><b>Tokutei Ginou (SSW)</b>: {{ __('needs Japanese N4/JFT A2 and a skills test.') }}</li>
                <li><b>{{ __('Internship (Ginou Jisshu)') }}</b>: {{ __('suits beginners; you are trained from the start.') }}</li>
                <li><b>{{ __('Engineer (Gijinkoku)') }}</b>: {{ __('for D3/S1 graduates in engineering or IT.') }}</li>
            </ul>
            <a href="{{ route('services') }}" wire:navigate class="font-bold underline underline-offset-4">{{ __('See each visa route in detail') }} →</a>
        </div>
        <p class="pj-disclaimer">{{ __('This check is a simulation for initial guidance. The final decision follows each job’s requirements and a consultation with our team.') }}</p>
    </aside>
</div>
