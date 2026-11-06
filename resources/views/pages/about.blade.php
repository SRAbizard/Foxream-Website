@extends('layouts.app')

@php
    $values = [
        ['title' => __('Integrity'),        'desc' => __('We are transparent with every cost, every step, every promise — no hidden surprises.')],
        ['title' => __('Professionalism'),  'desc' => __('Our mentors and partners are vetted to meet Japanese workplace standards.')],
        ['title' => __('Care'),             'desc' => __('We accompany students through every milestone, from first kana to first paycheck.')],
        ['title' => __('Growth'),           'desc' => __('We believe in lifelong learning — even after placement, you stay part of the community.')],
    ];

    $stats = [
        ['value' => \App\Support\SiteSettings::stat('students'),  'label' => __('Trained Students')],
        ['value' => \App\Support\SiteSettings::stat('workers'),   'label' => __('Workers placed in Japan')],
        ['value' => \App\Support\SiteSettings::stat('companies'), 'label' => __('Partner Companies')],
        ['value' => '8+',                                          'label' => __('Years of Experience')],
    ];
    // For companies in Japan: why us and how hiring works.
    $why = [
        ['01', __('Screened candidates'), __('We check documents, skills and motivation before a candidate ever reaches your interview.')],
        ['02', __('Japanese & work culture'), __('Candidates train at :learning: JLPT/JFT preparation plus Japanese workplace manners.')],
        ['03', __('Documents & visa handled'), __('We prepare the paperwork through to the Certificate of Eligibility and departure.')],
        ['04', __('Support after arrival'), __('A contact person for your new staff, so they settle in and stay.')],
    ];
    $process = [
        [__('Consultation'), __('Tell us the role, number of workers, visa route and timing.')],
        [__('Candidate selection'), __('We shortlist candidates who match your requirements.')],
        [__('Interview'), __('Online or on-site interviews with your team.')],
        [__('Visa & COE'), __('Contracts, medical check-up, documents and the COE.')],
        [__('Arrival & follow-up'), __('Departure, onboarding and support once they start.')],
    ];
    $categories = \App\Models\JobCategory::orderBy('sort_order')->get();
@endphp

@section('title', __('About Us') . ' — '.\App\Support\Brand::name())
@section('og_title', __('About :brand'))
@section('og_description', __('Learn how :brand helps Indonesian talent build successful careers in Japan.'))

@section('content')

{{-- HERO --}}
<x-jp.page-hero
    page="about"
    :eyebrow="__('About Us')"
    :title="__('Bridging Indonesian talent with Japanese opportunity.')"
    :subtitle="__(':brand is a career-and-education platform that prepares Indonesian workers and students for life and work in Japan — from language and culture training, to job placement and post-arrival support.')"
    :petals="12">
    @php $companyProfileUrl = \App\Support\SiteSettings::companyProfileUrl(); @endphp
    @if($companyProfileUrl)
        <a href="{{ $companyProfileUrl }}" target="_blank" rel="noopener noreferrer" download
           class="mt-8 inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 hover:shadow-lg hover:shadow-brand-500/40 transition group">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 group-hover:scale-110 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </span>
            <span>
                <span class="block text-[10px] uppercase tracking-wider opacity-80">{{ __('Download') }}</span>
                <span class="block">{{ __('Company Profile') }}</span>
            </span>
        </a>
    @endif
</x-jp.page-hero>

{{-- MISSION + VISION --}}
<section class="py-16 bg-surface-900/40">
    <div class="mx-auto max-w-7xl px-6 grid gap-8 md:grid-cols-2">
        <div class="glass-card p-8">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Our Mission') }}</p>
            <h2 class="mt-2 font-display text-2xl font-bold text-white">{{ __('Open the path to Japan, end-to-end.') }}</h2>
            <p class="mt-4 text-surface-300 leading-relaxed">
                {{ __('We support every step — from the first Japanese lesson, through interview prep and visa paperwork, to the moment your student lands at Narita. Nobody walks alone.') }}
            </p>
        </div>
        <div class="glass-card p-8">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Our Vision') }}</p>
            <h2 class="mt-2 font-display text-2xl font-bold text-white">{{ __('Indonesia & Japan: a stronger partnership through people.') }}</h2>
            <p class="mt-4 text-surface-300 leading-relaxed">
                {{ __('We want Indonesia to be the most trusted source of skilled, culturally-fluent talent for Japanese companies — and Japan to be a first-class career destination for ambitious Indonesians.') }}
            </p>
        </div>
    </div>
</section>

{{-- VALUES --}}
<section class="py-16">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Our Values') }}</p>
            <h2 class="mt-2 font-display text-3xl font-bold text-white">{{ __('What we stand for') }}</h2>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($values as $v)
                <div class="glass-card p-6">
                    <h3 class="font-display text-lg font-semibold text-white">{{ $v['title'] }}</h3>
                    <p class="mt-2 text-sm text-surface-400 leading-relaxed">{{ $v['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- STATS --}}
<section class="py-16 bg-surface-900/40">
    <div class="mx-auto max-w-5xl px-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-center">
            @foreach($stats as $s)
                <div class="rounded-2xl border border-surface-700/60 bg-surface-900/40 p-6">
                    <p class="font-display text-3xl font-bold text-white">{{ $s['value'] }}</p>
                    <p class="mt-1 text-xs text-surface-400 uppercase tracking-wider">{{ $s['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- For companies in Japan --}}
<section class="site-section">
    <div class="site-wrap">
        <div class="site-head">
            <span class="site-sticker is-pink">{{ __('For companies in Japan') }}</span>
            <h2>{{ __('Why companies work with us') }}</h2>
        </div>
        <div class="site-cards-4">
            @foreach($why as [$jp, $title, $desc])
                <div class="site-mini-step site-card">
                    <span class="n">{{ $jp }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="site-section pt-0!">
    <div class="site-wrap">
        <div class="site-head">
            <h2>{{ __('How hiring works') }}</h2>
        </div>
        <ol class="site-tickets" style="grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); list-style: none; padding: 0;">
            @foreach($process as $i => [$title, $desc])
                <li class="site-ticket">
                    <span class="n">0{{ $i + 1 }}</span>
                    <h4>{{ $title }}</h4>
                    <p class="text-sm opacity-80">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
        @if($categories->isNotEmpty())
            <div class="mt-8">
                <p class="text-xs font-extrabold tracking-[.14em] uppercase opacity-70 mb-3">{{ __('Fields we recruit for') }}</p>
                <div class="site-chips">
                    @foreach($categories as $c)<span class="site-chip">{{ $c->t('name') }}</span>@endforeach
                </div>
            </div>
        @endif
        <div class="mt-8">
            <a href="{{ route('contact', ['for' => 'employer']) }}" wire:navigate class="site-btn is-red">{{ __('Request workers') }} →</a>
        </div>
    </div>
</section>

{{-- OFFICES --}}
<section class="py-16">
    <div class="mx-auto max-w-7xl px-6">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Our Offices') }}</p>
            <h2 class="mt-2 font-display text-3xl font-bold text-white">{{ __('Where to find us') }}</h2>
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach(\App\Support\SiteSettings::offices() as $office)
                <a href="{{ $office['maps_url'] }}" target="_blank" rel="noopener noreferrer"
                   title="{{ __('Open in Google Maps') }}"
                   class="glass-card p-6 block hover:border-brand-500/50 hover:shadow-lg hover:shadow-brand-500/10 transition group">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600/20 text-brand-400 group-hover:scale-110 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="mt-4 font-display text-lg font-semibold text-white group-hover:text-brand-400">{{ $office['city'] }}</h3>
                    <p class="text-xs text-surface-400">{{ $office['country'] }}</p>
                    <p class="mt-3 text-sm text-surface-300">{{ $office['address'] }}</p>
                    <p class="mt-4 inline-flex items-center gap-1 text-xs text-brand-400 opacity-0 group-hover:opacity-100 transition">
                        {{ __('Open in Google Maps') }}
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </p>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- "Have questions?" CTA removed — duplicates the WhatsApp CTA in the global footer. --}}

@endsection
