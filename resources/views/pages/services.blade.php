@extends('layouts.app')

@section('title', __('Services') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Three routes to work in Japan — :brand'))
@section('og_description', __('Tokutei Ginou, Internship and Engineer: who each route fits, what you need, and every step we handle with you.'))

@php
    use App\Support\HomepageDemoData as Demo;

    // Requirement copy per route type (see MatchingData::visaRoutes()).
    $copy = [
        'ssw' => [
            'for'  => __('For you if you already know basic Japanese and want to work straight away on a Japanese salary.'),
            'reqs' => [__('Pass a Japanese test: JLPT N4 or JFT-Basic (A2)'), __('Pass the skills test for your field'), __('At least 18 years old and in good health')],
            'note' => __('Specified Skilled Worker (i) allows a total stay of up to 5 years.'),
        ],
        'intern' => [
            'for'  => __('For beginners with no work experience in Japan who want to learn while working.'),
            'reqs' => [__('At least 18 years old, high school / vocational graduate'), __('Take language and work-culture training before departure'), __('Physically and mentally healthy')],
            'note' => __('A contract-based programme with a host company in Japan.'),
        ],
        'engineer' => [
            'for'  => __('For diploma (D3) or bachelor (S1) graduates who want to work as professionals.'),
            'reqs' => [__('A D3/S1 degree matching the job, or related work experience'), __('Japanese level as required by the company (usually N3 or higher)'), __('A work contract with a Japanese company')],
            'note' => __('The visa can be renewed for as long as you keep working.'),
        ],
    ];
    $support = [
        ['01', __('Language training'), __('JLPT/JFT preparation at :learning, our Japanese language platform.')],
        ['02', __('Mensetsu coaching'), __('Interview practice, self-introduction in Japanese and what Japanese companies look for.')],
        ['03', __('Documents & visa'), __('Passport, medical check-up, document sending and the Certificate of Eligibility.')],
        ['04', __('Support after arrival'), __('Help settling in and a contact person once you have started work in Japan.')],
    ];
    $academyUrl = \App\Support\SiteSettings::academyUrl();
@endphp

@section('content')
<x-jp.page-hero
    :eyebrow="__('Services')"
    :title="__('Three routes to work in Japan.')"
    :subtitle="__('Each route has its own requirements. Find the one that fits you, then let us handle every step with you.')"
    :petals="8" />

<section class="site-section pt-0!">
    <div class="site-wrap">
        @forelse($routes as $r)
            @php $c = $copy[$r['type']] ?? null; @endphp
            <article class="site-route-card site-card" id="{{ $r['slug'] }}">
                <div class="grid gap-4 content-start">
                    <h2>{{ $r['name'] }}</h2>
                    @if($r['desc'])<p class="site-lede">{{ $r['desc'] }}</p>@endif
                    @if($c)
                        <p class="font-semibold">{{ $c['for'] }}</p>
                        <div>
                            <p class="text-xs font-extrabold tracking-[.14em] uppercase opacity-70 mb-2">{{ __('What you need') }}</p>
                            <ul class="reqs">@foreach($c['reqs'] as $req)<li>{{ $req }}</li>@endforeach</ul>
                        </div>
                        <p class="site-note">{{ $c['note'] }}</p>
                    @endif
                    <div class="flex flex-wrap gap-3 mt-2">
                        <a href="{{ route('job.index', ['visa' => $r['slug']]) }}" wire:navigate class="site-btn is-red">
                            {{ trans_choice('{0} See jobs|{1} See :count job|[2,*] See :count jobs', $r['jobs'], ['count' => $r['jobs']]) }} →
                        </a>
                        <a href="{{ route('job.index') }}#cek" wire:navigate class="site-btn">{{ __('Check your match') }}</a>
                    </div>
                </div>
                <div class="min-w-0">
                    @if($r['workflow'])
                        <p class="text-xs font-extrabold tracking-[.14em] uppercase opacity-70 mb-3">{{ __('Step by step') }}</p>
                        <div class="site-tickets">
                            @foreach($r['workflow']['steps'] as $step)
                                <div class="site-ticket">
                                    <span class="n">{{ str_pad($step['n'], 2, '0', STR_PAD_LEFT) }}</span>
                                    <h4>{{ Demo::pick($step['title']) }}</h4>
                                    @if(! empty($step['badge']))
                                        <span class="badge {{ $step['badge']['color'] }}">{{ Demo::pick($step['badge']['label']) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if(! empty($r['workflow']['notes']))
                            <div class="site-note mt-4 grid gap-1">
                                @foreach($r['workflow']['notes'] as $note)<p>• {{ Demo::pick($note) }}</p>@endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </article>
        @empty
            <div class="site-empty site-card"><p class="site-lede mx-auto">{{ __('Visa routes are being prepared. Talk to us on WhatsApp in the meantime.') }}</p></div>
        @endforelse
    </div>
</section>

<section class="site-section pt-0!">
    <div class="site-wrap">
        <div class="site-head">
            <h2>{{ __('Support at every step') }}</h2>
            <p class="site-lede">{{ __('Whichever route you choose, the same team stays with you from the first consultation to your first day at work.') }}</p>
        </div>
        <div class="site-cards-4">
            @foreach($support as $i => [$jp, $title, $desc])
                <div class="site-mini-step site-card">
                    <span class="n">{{ $jp }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $desc }}</p>
                    @if($i === 0 && $academyUrl)
                        <a href="{{ $academyUrl }}" target="_blank" rel="noopener" class="font-bold underline underline-offset-4 text-sm">{{ __('Open :learning') }} ↗</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
