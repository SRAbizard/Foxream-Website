@extends('layouts.app')
@section('title', $event->t('title') . ' — '.\App\Support\Brand::name())
@section('og_type', 'event')
@section('og_title', $event->t('title'))
@section('og_description', strip_tags($event->t('description')))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $event->t('title'),
    'description' => strip_tags($event->t('description')),
    'startDate' => $event->starts_at->toAtomString(),
    'endDate' => optional($event->ends_at)->toAtomString(),
    'location' => ['@type' => 'Place', 'name' => $event->t('location')],
    'organizer' => ['@type' => 'Organization', 'name' => $event->t('organizer')],
    'image' => $event->image_url ?: asset('images/brand/og.png'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<article class="mx-auto max-w-4xl px-6 pt-14 pb-20">
    <a href="{{ route('event.index') }}" class="text-sm text-surface-400 hover:text-brand-400 inline-flex items-center gap-1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Back to Events') }}
    </a>

    @if($event->category)
        <span class="mt-6 inline-block text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $event->category->t('name') }}</span>
    @endif

    <h1 class="mt-3 font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">{{ $event->t('title') }}</h1>

    @if($event->image_url)
        <img src="{{ $event->image_url }}" alt="" class="mt-8 w-full aspect-video object-cover rounded-2xl">
    @endif

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="glass-card p-5">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('When') }}</p>
            <p class="mt-2 font-semibold text-white">{{ $event->starts_at->isoFormat('D MMMM YYYY') }}</p>
            <p class="text-sm text-surface-400">{{ $event->starts_at->format('H:i') }}{{ $event->ends_at ? ' – '.$event->ends_at->format('H:i') : '' }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Where') }}</p>
            <p class="mt-2 font-semibold text-white">{{ $event->t('location') }}</p>
        </div>
        <div class="glass-card p-5">
            <p class="text-xs uppercase tracking-wider text-brand-400 font-semibold">{{ __('Organizer') }}</p>
            <p class="mt-2 font-semibold text-white">{{ $event->t('organizer') }}</p>
        </div>
    </div>

    <div class="prose prose-invert prose-headings:font-display prose-headings:text-white prose-a:text-brand-400 mt-10 max-w-none text-surface-200 leading-relaxed">
        {{ \App\Support\SafeHtml::clean($event->t('description')) }}
    </div>

    @if($event->registration_url)
        <div class="mt-10">
            <a href="{{ $event->registration_url }}" target="_blank" rel="noopener" class="btn-brand">
                {{ __('Register for this event') }}
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    @endif
</article>
@endsection
