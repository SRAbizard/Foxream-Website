{{-- "Misi ke Jepang" backdrop for every Filament auth page (login,
     register, password reset, email verification) in both panels.
     Desktop: illustrated mission panel on the left, the form card on the
     right. The login wallpaper (Website Settings → Login page wallpaper,
     or the bundled Japan photo) fills the whole page under a wash, like
     the homepage hero; petals fall over it. Styles live in
     resources/css/filament/theme.css under `.pj-auth`. --}}
@php
    $route = request()->route()?->getName() ?? '';
    $variant = match (true) {
        str_contains($route, 'filament.admin') => 'admin',
        str_contains($route, 'register')       => 'register',
        default                                => 'login',
    };
    [$title, $sub] = [
        'login'    => [__('Continue your mission.'), __('Log in to follow your applications, documents, and mensetsu schedule.')],
        'register' => [__('Start your mission to Japan.'), __('Create a free account, complete your profile, then apply for official jobs from partner companies.')],
        'admin'    => [__('Mission control.'), __('Manage job postings, candidates, and website content.')],
    ][$variant];
    $photo = \App\Support\SiteSettings::loginBackgroundUrl();
@endphp

<div class="pj-auth" aria-hidden="true">
    <div class="pj-auth-bg" style="background-image: url('{{ $photo }}')"></div>

    <div class="pj-auth-art">
        <span class="pj-auth-sticker">日本へのミッション · {{ __('Mission to Japan') }}</span>
        <p class="pj-auth-title">{{ $title }}</p>
        <p class="pj-auth-sub">{{ $sub }}</p>

        <div class="pj-auth-scene">
            <svg class="pj-auth-fuji" viewBox="0 0 400 200">
                <path d="M0 200 L120 70 Q150 40 170 38 L230 38 Q250 40 280 70 L400 200 Z" fill="#2B4795" stroke="var(--pj-line)" stroke-width="3" stroke-linejoin="round"/>
                <path d="M148 58 Q160 40 172 38 L228 38 Q240 40 252 58 L236 72 L222 60 L208 76 L196 60 L182 74 L166 62 Z" fill="#FFFAF2" stroke="var(--pj-line)" stroke-width="3" stroke-linejoin="round"/>
            </svg>
            <div class="pj-auth-pass">
                <div class="main">
                    <span class="top">搭乗券 · BOARDING PASS</span>
                    <span class="route"><b>CGK</b><i></i><b>NRT</b></span>
                    <span class="meta">{{ __('Passenger') }} <b>{{ __('You') }}</b> · Gate <b>{{ $variant === 'admin' ? 'Admin' : 'Mensetsu' }}</b></span>
                </div>
                <div class="stub">出発</div>
            </div>
        </div>
    </div>

    <div class="pj-auth-petals">
        @for($i = 0; $i < 12; $i++)
            <span style="left: {{ ($i * 37 + 11) % 100 }}%; --d: {{ 9 + ($i * 7) % 9 }}s; --delay: -{{ ($i * 13) % 12 }}s; --s: {{ 10 + ($i * 5) % 9 }}px"></span>
        @endfor
    </div>
</div>
