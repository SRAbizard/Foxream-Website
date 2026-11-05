@extends('layouts.app')

@section('title', __('For Employers') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Hire skilled Indonesian workers — :brand'))
@section('og_description', __('Screened candidates, Japanese language training and full visa support for companies in Japan.'))

@php
    $why = [
        ['選考', __('Screened candidates'), __('We check documents, skills and motivation before a candidate ever reaches your interview.')],
        ['日本語', __('Japanese & work culture'), __('Candidates train at :learning: JLPT/JFT preparation plus Japanese workplace manners.')],
        ['ビザ', __('Documents & visa handled'), __('We prepare the paperwork through to the Certificate of Eligibility and departure.')],
        ['支援', __('Support after arrival'), __('A contact person for your new staff, so they settle in and stay.')],
    ];
    $process = [
        [__('Consultation'), __('Tell us the role, number of workers, visa route and timing.')],
        [__('Candidate selection'), __('We shortlist candidates who match your requirements.')],
        ['Mensetsu', __('Online or on-site interviews with your team.')],
        [__('Visa & COE'), __('Contracts, medical check-up, documents and the COE.')],
        [__('Arrival & follow-up'), __('Departure, onboarding and support once they start.')],
    ];
@endphp

@section('content')
<x-jp.page-hero
    :eyebrow="__('For Employers') . ' · 企業の皆様へ'"
    :title="__('Hire skilled Indonesian talent, ready to work.')"
    :subtitle="__('We recruit, train and prepare Indonesian workers for companies in Japan, and stay with them after they arrive.')"
    :petals="8">
    <div class="mt-7 flex flex-wrap gap-3">
        <a href="#inquiry" class="pj-btn is-red">{{ __('Request workers') }} →</a>
        <a href="{{ route('services') }}" wire:navigate class="pj-btn">{{ __('Visa routes we handle') }}</a>
    </div>
</x-jp.page-hero>

<section class="pj-section pt-0!">
    <div class="pj-wrap">
        <div class="pj-head">
            <h2><span class="jp">選ばれる理由</span>{{ __('Why companies work with us') }}</h2>
        </div>
        <div class="pj-cards-4">
            @foreach($why as [$jp, $title, $desc])
                <div class="pj-mini-step pj-card">
                    <span class="n">{{ $jp }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="pj-section pt-0!">
    <div class="pj-wrap">
        <div class="pj-head">
            <h2><span class="jp">流れ</span>{{ __('How hiring works') }}</h2>
        </div>
        <ol class="pj-tickets" style="grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); list-style: none; padding: 0;">
            @foreach($process as $i => [$title, $desc])
                <li class="pj-ticket">
                    <span class="n">0{{ $i + 1 }}</span>
                    <h4>{{ $title }}</h4>
                    <p class="text-sm opacity-80">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
        @if($categories->isNotEmpty())
            <div class="mt-8">
                <p class="text-xs font-extrabold tracking-[.14em] uppercase opacity-70 mb-3">{{ __('Fields we recruit for') }}</p>
                <div class="pj-chips">
                    @foreach($categories as $c)<span class="pj-chip">{{ $c->t('name') }}</span>@endforeach
                </div>
            </div>
        @endif
    </div>
</section>

<section class="pj-section pt-0!" id="inquiry">
    <div class="pj-wrap pj-employer">
        <div class="pj-head">
            <span class="pj-sticker is-pink">{{ __('Free consultation') }} · 無料相談</span>
            <h2><span class="jp">お問い合わせ</span>{{ __('Request workers') }}</h2>
            <p class="pj-lede">{{ __('Tell us what you need. Our team replies within 1–2 business days, in Japanese, English or Indonesian.') }}</p>
        </div>

        <form action="{{ route('employers.store') }}" method="POST" class="pj-form pj-card">
            @csrf
            <x-honeypot />

            @if (session('status'))
                <div class="pj-alert is-ok" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="pj-alert is-err" role="alert">{{ __('Please correct the errors below:') }}</div>
            @endif

            <div class="pj-form-grid">
                <label>{{ __('Company name') }} <span class="req">*</span>
                    <input class="pj-input" type="text" name="company" required maxlength="160" value="{{ old('company') }}">
                    @error('company')<span class="pj-err">{{ $message }}</span>@enderror
                </label>
                <label>{{ __('Contact person') }} <span class="req">*</span>
                    <input class="pj-input" type="text" name="name" required maxlength="120" value="{{ old('name') }}">
                    @error('name')<span class="pj-err">{{ $message }}</span>@enderror
                </label>
                <label>{{ __('Email') }} <span class="req">*</span>
                    <input class="pj-input" type="email" name="email" required maxlength="191" value="{{ old('email') }}">
                    @error('email')<span class="pj-err">{{ $message }}</span>@enderror
                </label>
                <label>{{ __('Phone') }}
                    <input class="pj-input" type="tel" name="phone" maxlength="40" value="{{ old('phone') }}">
                </label>
                <label>{{ __('Location (prefecture / city)') }}
                    <input class="pj-input" type="text" name="location" maxlength="120" value="{{ old('location') }}" placeholder="Aichi, Nagoya">
                </label>
                <label>{{ __('Field') }}
                    <select class="pj-input" name="field">
                        <option value="">—</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->t('name') }}" @selected(old('field') === $c->t('name'))>{{ $c->t('name') }}</option>
                        @endforeach
                        <option value="{{ __('Other') }}" @selected(old('field') === __('Other'))>{{ __('Other') }}</option>
                    </select>
                </label>
                <label>{{ __('Visa route') }}
                    <select class="pj-input" name="visa">
                        <option value="">{{ __('Not sure yet') }}</option>
                        @foreach($visas as $v)
                            <option value="{{ $v->t('name') }}" @selected(old('visa') === $v->t('name'))>{{ $v->t('name') }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ __('Workers needed') }}
                    <input class="pj-input" type="number" name="workers" min="1" max="999" value="{{ old('workers') }}">
                    @error('workers')<span class="pj-err">{{ $message }}</span>@enderror
                </label>
            </div>
            <label>{{ __('Preferred start') }}
                <input class="pj-input" type="text" name="start" maxlength="120" value="{{ old('start') }}" placeholder="{{ __('e.g. April 2027') }}">
            </label>
            <label>{{ __('Message') }}
                <textarea class="pj-input" name="message" rows="5" maxlength="5000" placeholder="{{ __('Job details, working conditions, anything we should know…') }}">{{ old('message') }}</textarea>
            </label>
            <div><button type="submit" class="pj-btn is-red">{{ __('Send request') }} →</button></div>
        </form>
    </div>
</section>
@endsection
