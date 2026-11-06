@extends('layouts.app')
@section('title', __('Job Vacancies') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Find your Japan job — :brand'))

@php
    $activeFilterCount = collect(['visa','category','region','city','q'])->filter(fn ($k) => filled(request($k)))->count();
@endphp

@section('content')
<x-jp.page-hero
    page="jobs"
    :eyebrow="__('Careers')"
    :title="__('Find your job in Japan')"
    :subtitle="__('Browse verified opportunities from our partner companies. Filter by visa, role, or city.')"
    :petals="10" />

{{-- Filters as collapsible dropdown --}}
<section class="mx-auto max-w-7xl px-6 mb-10">
    <form method="GET" id="jobs-filter-form">
        <details class="group glass-card" @if($activeFilterCount > 0) open @endif>
            <summary class="flex items-center justify-between gap-3 cursor-pointer list-none px-5 py-4 select-none">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600/20 text-brand-400 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-display font-semibold text-white">{{ __('Filter & Search') }}</p>
                        <p class="text-xs text-surface-400 truncate">
                            @if($activeFilterCount > 0)
                                {{ trans_choice('{1} :count active filter|[2,*] :count active filters', $activeFilterCount, ['count' => $activeFilterCount]) }}
                            @else
                                {{ __('Narrow results by visa, role, city, or keyword.') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($activeFilterCount > 0)
                        <a href="{{ route('job.index') }}"
                           class="text-xs text-surface-300 hover:text-brand-400 px-3 py-1.5 rounded-lg border border-surface-700 hover:border-brand-500/50 transition">
                            {{ __('Reset filter') }}
                        </a>
                    @endif
                    <svg class="h-5 w-5 text-surface-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </summary>

            <div class="border-t border-surface-800/70 px-5 py-5 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <label class="block">
                    <span class="text-xs text-surface-400">{{ __('Visa') }}</span>
                    <select name="visa" class="mt-1.5 w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">{{ __('All') }}</option>
                        @foreach($visas as $v)
                            <option value="{{ $v->slug }}" @selected(request('visa') === $v->slug)>{{ $v->t('name') }} ({{ $v->vacancies_count }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-surface-400">{{ __('Category') }}</span>
                    <select name="category" class="mt-1.5 w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">{{ __('All') }}</option>
                        @foreach($jobCats as $c)
                            <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->t('name') }} ({{ $c->vacancies_count }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-surface-400">{{ __('Region') }}</span>
                    <select name="region" class="mt-1.5 w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">{{ __('All of Japan') }}</option>
                        @foreach($regions as $key => $r)
                            <option value="{{ $key }}" @selected(request('region') === $key)>{{ __($r['name']) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-surface-400">{{ __('City') }}</span>
                    <input type="text" name="city" value="{{ request('city') }}" placeholder="{{ __('e.g. Tokyo') }}"
                           class="mt-1.5 w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3 py-2 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                </label>
                <label class="block">
                    <span class="text-xs text-surface-400">{{ __('Keyword') }}</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search…') }}"
                           class="mt-1.5 w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3 py-2 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-surface-800/70 px-5 py-4">
                <a href="{{ route('job.index') }}" class="btn-ghost text-sm">{{ __('Reset filter') }}</a>
                <button class="btn-brand text-sm">{{ __('Apply filter') }}</button>
            </div>
        </details>
    </form>
</section>

{{-- Vacancy grid --}}
<section class="mx-auto max-w-7xl px-6 pb-20">
    @if($vacancies->isEmpty())
        <div class="glass-card p-10 text-center">
            <p class="text-surface-300">{{ __('No vacancies match your filters.') }}</p>
            @if($activeFilterCount > 0)
                <a href="{{ route('job.index') }}" class="mt-4 inline-block btn-ghost text-sm">{{ __('Reset filter') }}</a>
            @endif
        </div>
    @else
        <div class="flex items-baseline justify-between flex-wrap gap-2 mb-5">
            <p class="text-sm text-surface-400">
                {{ trans_choice('{1} :count vacancy found|[2,*] :count vacancies found', $vacancies->total(), ['count' => $vacancies->total()]) }}
            </p>
        </div>
        <div class="reveal reveal-stagger grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($vacancies as $v)
                <a href="{{ route('job.show', $v->slug) }}" class="glass-card p-5 block hover:border-brand-500/50 transition group">
                    <div class="flex items-start gap-3">
                        @if($v->company->logo_url)
                            <img src="{{ $v->company->logo_url }}" alt="" class="h-12 w-12 rounded-xl object-cover">
                        @else
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 flex items-center justify-center font-display font-bold text-white text-sm shrink-0">
                                {{ \Illuminate\Support\Str::of($v->company->name)->substr(0,1)->upper() }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold text-white truncate group-hover:text-brand-400 transition">{{ $v->t('title') }}</h2>
                            <p class="text-xs text-surface-400 truncate">{{ $v->company->localName() }}</p>
                        </div>
                        @if($v->is_featured)
                            <span class="text-brand-400 text-xs">★</span>
                        @endif
                    </div>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @if($v->jobCategory)<span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-surface-800 text-surface-300">{{ $v->jobCategory->t('name') }}</span>@endif
                        @if($v->visaCategory)<span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $v->visaCategory->t('name') }}</span>@endif
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-surface-400">
                        <span class="flex items-center gap-1 truncate">
                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $v->location_display }}
                        </span>
                        <span class="flex items-center gap-1 text-emerald-400 truncate">
                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8v1m0 14v1m0-16a8 8 0 100 16 8 8 0 000-16z"/></svg>
                            {{ $v->salary_range ?? '—' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">{{ $vacancies->links() }}</div>
    @endif
</section>

{{-- Matching tools: quiz + region map (shared with resources/js/home.js) --}}
<section class="pj-section bg-[var(--pj-washi-2)]" data-pj-home id="matching">
    <div class="pj-scallop absolute top-0 inset-x-0" style="--pj-scallop: var(--pj-washi)" aria-hidden="true"></div>
    <div class="pj-wrap">
        <div class="pj-match-row">
            <div id="cek" class="scroll-mt-28">@include('partials.match-quiz', ['quiz' => $matching->quiz()])</div>
            <div id="peta" class="scroll-mt-28">@include('partials.job-map', ['regions' => $matching->regions()])</div>
        </div>
    </div>
    {{-- MatchingData::json() is JSON_HEX_TAG-encoded, so printing it raw is safe. --}}
    <script type="application/json" id="pj-home-data">{!! $matching->json() !!}</script>
</section>
@endsection
