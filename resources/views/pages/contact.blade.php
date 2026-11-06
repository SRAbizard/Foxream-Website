@extends('layouts.app')

@section('title', __('Contact') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Contact :brand'))
@section('og_description', __('For candidates and for companies in Japan: free consultation with our team.'))

@php
    $whatsapp = \App\Support\SiteSettings::contact('whatsapp');
    $phone    = \App\Support\SiteSettings::contact('phone');
    $email    = \App\Support\SiteSettings::contact('email');
    $offices  = \App\Support\SiteSettings::offices();
@endphp

@section('content')
<x-jp.page-hero
    :eyebrow="__('Contact') . ' · お問い合わせ'"
    :title="__('Let\'s talk.')"
    :subtitle="__('Looking for a job in Japan, or for workers from Indonesia? Free consultation, in Indonesian, Japanese or English.')"
    :petals="10" />

<div x-data="{ who: @js($for) }">
    <section class="pb-16">
        <div class="pj-wrap grid gap-8 lg:grid-cols-3">

            <div class="lg:col-span-2" id="contact-form">
                {{-- Who is writing: the form follows. --}}
                <div class="pj-contact-who" role="tablist" aria-label="{{ __('I am') }}">
                    <button type="button" role="tab" :aria-selected="who === 'candidate'" :class="who === 'candidate' && 'is-on'" @click="who = 'candidate'">
                        {{ __('A candidate') }} <small>{{ __('looking for a job in Japan') }}</small>
                    </button>
                    <button type="button" role="tab" :aria-selected="who === 'employer'" :class="who === 'employer' && 'is-on'" @click="who = 'employer'">
                        {{ __('A company') }} · 企業の方 <small>{{ __('looking for workers') }}</small>
                    </button>
                </div>

                {{-- Candidates --}}
                <form x-show="who === 'candidate'" @if($for !== 'candidate') x-cloak @endif action="{{ route('contact.store') }}" method="POST" class="pj-form pj-card">
                    @csrf
                    <x-honeypot />
                    <input type="hidden" name="for" value="candidate">

                    @if (session('status') && $for === 'candidate')
                        <div class="pj-alert is-ok" role="status">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any() && $for === 'candidate')
                        <div class="pj-alert is-err" role="alert">{{ __('Please correct the errors below:') }}</div>
                    @endif

                    <div class="pj-form-grid">
                        <label><span>{{ __('Name') }} <span class="req">*</span></span>
                            <input class="pj-input" type="text" name="name" required maxlength="120" value="{{ $for === 'candidate' ? old('name') : '' }}" placeholder="{{ __('Your full name') }}">
                            @if($for === 'candidate') @error('name')<span class="pj-err">{{ $message }}</span>@enderror @endif
                        </label>
                        <label><span>{{ __('Email') }} <span class="req">*</span></span>
                            <input class="pj-input" type="email" name="email" required maxlength="191" value="{{ $for === 'candidate' ? old('email') : '' }}" placeholder="you@example.com">
                            @if($for === 'candidate') @error('email')<span class="pj-err">{{ $message }}</span>@enderror @endif
                        </label>
                        <label><span>{{ __('Phone') }} <span class="opt">({{ __('optional') }})</span></span>
                            <input class="pj-input" type="tel" name="phone" maxlength="32" value="{{ $for === 'candidate' ? old('phone') : '' }}" placeholder="+62 8xx-xxxx-xxxx">
                        </label>
                        <label><span>{{ __('Subject') }} <span class="req">*</span></span>
                            <input class="pj-input" type="text" name="subject" required maxlength="191" value="{{ $for === 'candidate' ? old('subject') : '' }}" placeholder="{{ __('What is your question about?') }}">
                            @if($for === 'candidate') @error('subject')<span class="pj-err">{{ $message }}</span>@enderror @endif
                        </label>
                    </div>
                    <label><span>{{ __('Message') }} <span class="req">*</span></span>
                        <textarea class="pj-input" name="message" required rows="6" minlength="10" maxlength="5000" placeholder="{{ __('Tell us a bit about what you need…') }}">{{ $for === 'candidate' ? old('message') : '' }}</textarea>
                        @if($for === 'candidate') @error('message')<span class="pj-err">{{ $message }}</span>@enderror @endif
                    </label>
                    <div class="pj-form-foot">
                        <p>{!! __('By submitting, you agree to our :link.', ['link' => '<a href="'.e(route('legal.privacy')).'" class="underline">'.e(__('Privacy Policy')).'</a>']) !!}</p>
                        <button type="submit" class="pj-btn is-red">{{ __('Send message') }} →</button>
                    </div>
                </form>

                {{-- Companies in Japan --}}
                <form x-show="who === 'employer'" @if($for !== 'employer') x-cloak @endif action="{{ route('contact.employer') }}" method="POST" class="pj-form pj-card">
                    @csrf
                    <x-honeypot />
                    <input type="hidden" name="for" value="employer">

                    @if (session('status') && $for === 'employer')
                        <div class="pj-alert is-ok" role="status">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any() && $for === 'employer')
                        <div class="pj-alert is-err" role="alert">{{ __('Please correct the errors below:') }}</div>
                    @endif

                    <p class="pj-lede">{{ __('Tell us what you need. Our team replies within 1–2 business days, in Japanese, English or Indonesian.') }}</p>

                    <div class="pj-form-grid">
                        <label><span>{{ __('Company name') }} <span class="req">*</span></span>
                            <input class="pj-input" type="text" name="company" required maxlength="160" value="{{ old('company') }}">
                            @error('company')<span class="pj-err">{{ $message }}</span>@enderror
                        </label>
                        <label><span>{{ __('Contact person') }} <span class="req">*</span></span>
                            <input class="pj-input" type="text" name="name" required maxlength="120" value="{{ $for === 'employer' ? old('name') : '' }}">
                            @if($for === 'employer') @error('name')<span class="pj-err">{{ $message }}</span>@enderror @endif
                        </label>
                        <label><span>{{ __('Email') }} <span class="req">*</span></span>
                            <input class="pj-input" type="email" name="email" required maxlength="191" value="{{ $for === 'employer' ? old('email') : '' }}">
                            @if($for === 'employer') @error('email')<span class="pj-err">{{ $message }}</span>@enderror @endif
                        </label>
                        <label>{{ __('Phone') }}
                            <input class="pj-input" type="tel" name="phone" maxlength="40" value="{{ $for === 'employer' ? old('phone') : '' }}">
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
                        <textarea class="pj-input" name="message" rows="5" maxlength="5000" placeholder="{{ __('Job details, working conditions, anything we should know…') }}">{{ $for === 'employer' ? old('message') : '' }}</textarea>
                    </label>
                    <div class="pj-form-foot">
                        <p>{!! __('By submitting, you agree to our :link.', ['link' => '<a href="'.e(route('legal.privacy')).'" class="underline">'.e(__('Privacy Policy')).'</a>']) !!}</p>
                        <button type="submit" class="pj-btn is-red">{{ __('Send request') }} →</button>
                    </div>
                </form>
            </div>

            {{-- Reach us directly --}}
            <aside class="space-y-4">
                @if($whatsapp || $email)
                    <div class="pj-card pj-contact-card">
                        <p class="pj-contact-label">{{ __('Reach us directly') }}</p>
                        @if($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">
                                <span class="ico is-green"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.4-1.1c-.3-.1-.6-.1-.8.1l-1 1.1c-1.7-.9-3.1-2.2-4-3.9l1-1.1c.2-.2.2-.5.1-.7L9.4 6.4c-.1-.3-.4-.4-.7-.4H7.3c-.3 0-.6.2-.7.5-.2 3.2 1.1 6.3 3.4 8.6 2.3 2.3 5.3 3.6 8.6 3.4.3 0 .5-.4.5-.7v-1.5c0-.3-.2-.6-.4-.7l-1.2-.2zM12 2a10 10 0 100 20 10 10 0 000-20z"/></svg></span>
                                <span><small>WhatsApp</small>{{ $phone ?: '+'.$whatsapp }}</span>
                            </a>
                        @endif
                        @if($email)
                            <a href="mailto:{{ $email }}">
                                <span class="ico"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M3 8v10a2 2 0 002 2h14a2 2 0 002-2V8M3 8l2-2h14l2 2"/></svg></span>
                                <span><small>Email</small>{{ $email }}</span>
                            </a>
                        @endif
                    </div>
                @endif

                @if(count($offices))
                    <div class="pj-card pj-contact-card">
                        <p class="pj-contact-label">{{ __('Offices') }}</p>
                        @foreach($offices as $office)
                            <a href="{{ $office['maps_url'] }}" target="_blank" rel="noopener noreferrer" title="{{ __('Open in Google Maps') }}">
                                <span class="ico"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg></span>
                                <span><b>{{ $office['city'] }}</b> · {{ $office['country'] }}<small>{{ $office['address'] }}</small></span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </section>
</div>
@endsection
