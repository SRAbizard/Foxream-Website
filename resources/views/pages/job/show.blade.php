@extends('layouts.app')
@section('title', $vacancy->t('title') . ' — ' . $vacancy->company->name)
@section('og_type', 'website')
@section('og_title', $vacancy->t('title').' · '.$vacancy->company->name)
@section('og_description', \Illuminate\Support\Str::limit(strip_tags($vacancy->t('description')), 180))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'JobPosting',
    'title' => $vacancy->t('title'),
    'description' => strip_tags($vacancy->t('description')),
    'datePosted' => optional($vacancy->published_at)->toAtomString(),
    'validThrough' => optional($vacancy->expires_at)->toAtomString(),
    'employmentType' => strtoupper($vacancy->employment_type),
    'hiringOrganization' => ['@type' => 'Organization', 'name' => $vacancy->company->name],
    'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $vacancy->location_city, 'addressRegion' => $vacancy->location_prefecture, 'addressCountry' => $vacancy->company->country ?? 'JP']],
    'baseSalary' => $vacancy->salary_min ? ['@type' => 'MonetaryAmount', 'currency' => $vacancy->salary_currency, 'value' => ['@type' => 'QuantitativeValue', 'minValue' => $vacancy->salary_min, 'maxValue' => $vacancy->salary_max, 'unitText' => strtoupper($vacancy->salary_period)]] : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<article class="mx-auto max-w-5xl px-6 pt-14 pb-20">
    <a href="{{ route('job.index') }}" class="text-sm text-surface-400 hover:text-brand-400 inline-flex items-center gap-1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Back to Job Vacancies') }}
    </a>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 flex items-start gap-4 flex-wrap">
        @if($vacancy->company->logo_url)
            <img src="{{ $vacancy->company->logo_url }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
        @else
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 flex items-center justify-center font-display font-bold text-white text-2xl shrink-0">
                {{ \Illuminate\Support\Str::of($vacancy->company->name)->substr(0,1)->upper() }}
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <p class="text-sm text-surface-400">{{ $vacancy->company->name }}</p>
            <h1 class="mt-1 font-display text-3xl sm:text-4xl font-extrabold text-white">{{ $vacancy->t('title') }}</h1>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @if($vacancy->jobCategory)<span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-surface-800 text-surface-300">{{ $vacancy->jobCategory->t('name') }}</span>@endif
                @if($vacancy->visaCategory)<span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $vacancy->visaCategory->t('name') }}</span>@endif
                <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-surface-800 text-surface-300">{{ __(\Illuminate\Support\Str::ucfirst($vacancy->employment_type)) }}</span>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="glass-card p-4">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Location') }}</p>
            <p class="mt-2 font-semibold text-white">{{ $vacancy->location_display }}</p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Salary') }}</p>
            <p class="mt-2 font-semibold text-emerald-300">{{ $vacancy->salary_range ?? '—' }} <span class="text-xs text-surface-400">/ {{ __($vacancy->salary_period) }}</span></p>
        </div>
        <div class="glass-card p-4">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Positions') }}</p>
            <p class="mt-2 font-semibold text-white">{{ $vacancy->positions }}</p>
        </div>
    </div>

    <div class="mt-10 grid gap-10 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-8">
            <section>
                <h2 class="font-display text-xl font-bold text-white">{{ __('Description') }}</h2>
                <div class="prose prose-invert mt-4 max-w-none">{{ \App\Support\SafeHtml::clean($vacancy->t('description')) }}</div>
            </section>
            @if(! empty($vacancy->getTranslations('requirements')))
                <section>
                    <h2 class="font-display text-xl font-bold text-white">{{ __('Requirements') }}</h2>
                    <div class="prose prose-invert mt-4 max-w-none">{{ \App\Support\SafeHtml::clean($vacancy->t('requirements')) }}</div>
                </section>
            @endif
            @if(! empty($vacancy->getTranslations('benefits')))
                <section>
                    <h2 class="font-display text-xl font-bold text-white">{{ __('Benefits') }}</h2>
                    <div class="prose prose-invert mt-4 max-w-none">{{ \App\Support\SafeHtml::clean($vacancy->t('benefits')) }}</div>
                </section>
            @endif

            {{-- Job detail PDF download (if uploaded) --}}
            @if($vacancy->job_order_public && \App\Support\JobOrder::filled($vacancy))
                <section>
                    <a href="{{ route('job.order-pdf', $vacancy->slug) }}" target="_blank" rel="noopener"
                       class="glass-card p-5 flex items-center gap-4 hover:border-brand-500/50 hover:shadow-lg hover:shadow-brand-500/10 transition group">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600/20 text-brand-400 shrink-0 group-hover:scale-110 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs uppercase tracking-wider font-semibold text-brand-400">PDF</p>
                            <p class="font-semibold text-white group-hover:text-brand-400 transition">{{ __('Job Order (Indonesian translation)') }}</p>
                            <p class="text-xs text-surface-400 mt-0.5">{{ __('Company profile, working conditions, salary and benefits') }}</p>
                        </div>
                        <svg class="h-5 w-5 text-surface-400 group-hover:text-brand-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </section>
            @endif
            @if($vacancy->detail_pdf_url)
                <section>
                    <a href="{{ $vacancy->detail_pdf_url }}" target="_blank" rel="noopener noreferrer" download
                       class="glass-card p-5 flex items-center gap-4 hover:border-brand-500/50 hover:shadow-lg hover:shadow-brand-500/10 transition group">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600/20 text-brand-400 shrink-0 group-hover:scale-110 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs uppercase tracking-wider font-semibold text-brand-400">{{ __('Download') }}</p>
                            <p class="font-semibold text-white group-hover:text-brand-400 transition">{{ __('Job Detail (Full Version)') }}</p>
                            <p class="text-xs text-surface-400 mt-0.5">{{ __('Complete spec — PDF / DOC / image') }}</p>
                        </div>
                        <svg class="h-5 w-5 text-surface-400 group-hover:text-brand-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </section>
            @endif
        </div>

        {{-- Apply card --}}
        <aside class="lg:col-span-1">
            <div class="sticky top-24 grid gap-5">
            <div class="glass-card p-6">
                @auth
                    @php($currentUser = auth()->user())
                    @if($currentUser->hasRole('student'))
                        @if($hasApplied)
                            <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-3 text-center">
                                <p class="text-sm font-semibold text-emerald-300">{{ __('You have applied') }}</p>
                                <p class="mt-1 text-xs text-surface-400">{{ __('Track status in your student dashboard.') }}</p>
                                <a href="/dashboard/applications" class="mt-3 inline-block text-xs text-brand-400 hover:text-brand-300">{{ __('Go to dashboard') }} →</a>
                            </div>
                        @elseif($blocker)
                            <h3 class="font-display text-lg font-bold text-white">{{ __('Apply for this position') }}</h3>
                            <div class="mt-3 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4">
                                <p class="text-sm text-amber-200">{{ $blocker['message'] }}</p>
                                <a href="{{ $blocker['url'] }}" class="mt-3 btn-brand w-full">{{ $blocker['action'] }}</a>
                            </div>
                        @else
                            <form method="POST" action="{{ route('job.apply', $vacancy->slug) }}">
                                @csrf
                                <h3 class="font-display text-lg font-bold text-white">{{ __('Apply for this position') }}</h3>
                                <p class="mt-1 text-xs text-surface-400">{{ __('Optional: tell the company why you fit.') }}</p>
                                <textarea name="cover_letter" rows="5" maxlength="3000" placeholder="{{ __('Cover letter (optional)') }}"
                                    class="mt-3 w-full rounded-xl border border-surface-700 bg-surface-900/60 px-3 py-2 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none"></textarea>
                                <button type="submit" class="mt-3 btn-brand w-full">
                                    {{ __('Submit application') }}
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
                                </button>
                            </form>
                        @endif
                    @else
                        {{-- Admin/superadmin can't apply --}}
                        <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4">
                            <p class="text-sm font-semibold text-amber-300">{{ __('Admin view') }}</p>
                            <p class="mt-2 text-xs text-surface-400">{{ __('You are signed in as :role. Only student accounts can apply for jobs.', ['role' => $currentUser->getRoleNames()->first() ?? 'admin']) }}</p>
                            <a href="/admin/job-vacancies" class="mt-4 inline-flex items-center gap-1 text-xs text-brand-400 hover:text-brand-300">
                                {{ __('Manage in admin panel') }} →
                            </a>
                        </div>
                    @endif
                @else
                    <h3 class="font-display text-lg font-bold text-white">{{ __('Want to apply?') }}</h3>
                    <p class="mt-1 text-sm text-surface-400">{{ __('Create a free account to submit applications and track their status.') }}</p>
                    <div class="mt-5 flex flex-col gap-3">
                        <a href="/dashboard/register" class="btn-brand w-full">{{ __('Register') }}</a>
                        <a href="/dashboard/login" class="btn-ghost text-sm w-full text-center">{{ __('Already have an account? Login') }}</a>
                    </div>
                @endauth
            </div>
            <x-fraud-notice compact />
            </div>
        </aside>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-16">
            <h2 class="font-display text-2xl font-bold text-white">{{ __('Similar opportunities') }}</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach($related as $r)
                    <a href="{{ route('job.show', $r->slug) }}" class="glass-card p-5 hover:border-brand-500/50 transition">
                        <p class="text-xs text-surface-400">{{ $r->company->name }}</p>
                        <h3 class="mt-1 font-semibold text-white">{{ $r->t('title') }}</h3>
                        <p class="mt-2 text-xs text-emerald-400">{{ $r->salary_range }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
