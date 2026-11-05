@extends('layouts.app')

@section('title', __('Contact') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Contact :brand'))
@section('og_description', __('Get in touch with our team for consultation or job opportunities in Japan.'))

@section('content')
<x-jp.page-hero
    :eyebrow="__('Contact')"
    :title="__('Let\'s talk about your Japan journey.')"
    :subtitle="__('Free consultation. Our team replies within 24 hours.')"
    :petals="10" />

<section class="pb-24">
    <div class="mx-auto max-w-7xl px-6 grid gap-10 lg:grid-cols-3">

        {{-- LEFT: Form --}}
        <div class="lg:col-span-2">
            <form id="contact-form" action="{{ route('contact.store') }}" method="POST" class="glass-card p-6 lg:p-10 space-y-5">
                @csrf
                <x-honeypot />

                @if (session('status'))
                    <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-emerald-300 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-xl border border-brand-500/40 bg-brand-500/10 px-4 py-3 text-brand-200 text-sm">
                        <p class="font-semibold">{{ __('Please correct the errors below:') }}</p>
                        <ul class="mt-1 list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-surface-200">{{ __('Name') }} <span class="text-brand-500">*</span></span>
                        <input type="text" name="name" required maxlength="120" value="{{ old('name') }}"
                            class="mt-1.5 block w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3.5 py-2.5 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            placeholder="{{ __('Your full name') }}">
                        @error('name')<span class="mt-1 block text-xs text-brand-400">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-surface-200">{{ __('Email') }} <span class="text-brand-500">*</span></span>
                        <input type="email" name="email" required maxlength="191" value="{{ old('email') }}"
                            class="mt-1.5 block w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3.5 py-2.5 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                            placeholder="you@example.com">
                        @error('email')<span class="mt-1 block text-xs text-brand-400">{{ $message }}</span>@enderror
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-surface-200">{{ __('Phone') }} <span class="text-surface-500 text-xs">({{ __('optional') }})</span></span>
                    <input type="tel" name="phone" maxlength="32" value="{{ old('phone') }}"
                        class="mt-1.5 block w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3.5 py-2.5 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                        placeholder="+62 8xx-xxxx-xxxx">
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-surface-200">{{ __('Subject') }} <span class="text-brand-500">*</span></span>
                    <input type="text" name="subject" required maxlength="191" value="{{ old('subject') }}"
                        class="mt-1.5 block w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3.5 py-2.5 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                        placeholder="{{ __('What is your question about?') }}">
                    @error('subject')<span class="mt-1 block text-xs text-brand-400">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-surface-200">{{ __('Message') }} <span class="text-brand-500">*</span></span>
                    <textarea name="message" required rows="6" minlength="10" maxlength="5000"
                        class="mt-1.5 block w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3.5 py-2.5 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                        placeholder="{{ __('Tell us a bit about what you need…') }}">{{ old('message') }}</textarea>
                    @error('message')<span class="mt-1 block text-xs text-brand-400">{{ $message }}</span>@enderror
                </label>

                <div class="flex items-center justify-between flex-wrap gap-3">
                    <p class="text-xs text-surface-500">{!! __('By submitting, you agree to our :link.', ['link' => '<a href="'.e(route('legal.privacy')).'" class="underline">'.e(__('Privacy Policy')).'</a>']) !!}</p>
                    <button type="submit" class="btn-brand">
                        {{ __('Send message') }}
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </form>
        </div>

        {{-- RIGHT: Contact info --}}
        <aside class="space-y-4">
            <div class="glass-card p-6">
                <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Reach us directly') }}</p>
                <div class="mt-5 space-y-4 text-sm">
                    <a href="https://wa.me/{{ \App\Support\SiteSettings::contact('whatsapp') }}" target="_blank" rel="noopener" class="flex items-start gap-3 text-surface-200 hover:text-brand-400 transition">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 shrink-0">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4l-2.4-1.1c-.3-.1-.6-.1-.8.1l-1 1.1c-1.7-.9-3.1-2.2-4-3.9l1-1.1c.2-.2.2-.5.1-.7L9.4 6.4c-.1-.3-.4-.4-.7-.4H7.3c-.3 0-.6.2-.7.5-.2 3.2 1.1 6.3 3.4 8.6 2.3 2.3 5.3 3.6 8.6 3.4.3 0 .5-.4.5-.7v-1.5c0-.3-.2-.6-.4-.7l-1.2-.2zM12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                        </span>
                        <span>
                            <span class="block text-xs text-surface-400">WhatsApp</span>
                            <span class="block">{{ \App\Support\SiteSettings::contact('phone') }}</span>
                        </span>
                    </a>
                    <a href="mailto:{{ \App\Support\SiteSettings::contact('email') }}" class="flex items-start gap-3 text-surface-200 hover:text-brand-400 transition">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500/15 text-brand-400 shrink-0">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M3 8v10a2 2 0 002 2h14a2 2 0 002-2V8M3 8l2-2h14l2 2"/></svg>
                        </span>
                        <span>
                            <span class="block text-xs text-surface-400">Email</span>
                            <span class="block">{{ \App\Support\SiteSettings::contact('email') }}</span>
                        </span>
                    </a>
                </div>
            </div>

            <div class="glass-card p-6">
                <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Offices') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach(\App\Support\SiteSettings::offices() as $office)
                        <li>
                            <a href="{{ $office['maps_url'] }}" target="_blank" rel="noopener noreferrer"
                               title="{{ __('Open in Google Maps') }}"
                               class="block -mx-2 px-2 py-1.5 rounded-lg hover:bg-surface-800/60 transition group">
                                <p class="font-semibold text-white group-hover:text-brand-400 inline-flex items-center gap-1.5">
                                    {{ $office['city'] }}
                                    <span class="text-surface-500 font-normal">· {{ $office['country'] }}</span>
                                    <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </p>
                                <p class="text-xs text-surface-400 mt-0.5">{{ $office['address'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</section>
@endsection
