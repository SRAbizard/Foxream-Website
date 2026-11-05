@extends('layouts.app')
@section('title', __('Events') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Upcoming events at :brand'))

@section('content')
<x-jp.page-hero
    :eyebrow="__('Events')"
    :title="__('Workshops, seminars, and job fairs')"
    :subtitle="__('Join our upcoming events to meet recruiters, mentors, and the :brand community.')"
    :petals="10" />

<section class="mx-auto max-w-7xl px-6 mb-6">
    <div class="flex items-center gap-2">
        <a href="{{ route('event.index', ['when' => 'upcoming']) }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ $filter === 'upcoming' ? 'bg-brand-600 text-white' : 'bg-surface-800 text-surface-300 hover:bg-surface-700' }}">
            {{ __('Upcoming') }}
        </a>
        <a href="{{ route('event.index', ['when' => 'past']) }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ $filter === 'past' ? 'bg-brand-600 text-white' : 'bg-surface-800 text-surface-300 hover:bg-surface-700' }}">
            {{ __('Past') }}
        </a>
    </div>
</section>

<section class="mx-auto max-w-7xl px-6 pb-20">
    @if($events->isEmpty())
        <div class="glass-card p-10 text-center">
            <p class="text-surface-300">{{ __('No events here yet — stay tuned.') }}</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($events as $event)
                <a href="{{ route('event.show', $event->slug) }}" class="glass-card overflow-hidden hover:border-brand-500/50 transition group flex flex-col">
                    @if($event->image_url)
                        <img src="{{ $event->image_url }}" alt="" class="aspect-video w-full object-cover">
                    @else
                        <div class="aspect-video w-full bg-gradient-to-br from-brand-700 to-brand-900 flex items-center justify-center">
                            <div class="text-center">
                                <p class="font-display text-4xl font-bold text-white">{{ $event->starts_at->format('d') }}</p>
                                <p class="text-xs text-brand-200 uppercase tracking-wider">{{ $event->starts_at->isoFormat('MMM YYYY') }}</p>
                            </div>
                        </div>
                    @endif
                    <div class="p-5 flex flex-col flex-1">
                        @if($event->category)
                            <span class="inline-block self-start text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $event->category->t('name') }}</span>
                        @endif
                        <h2 class="mt-3 font-display text-lg font-bold text-white group-hover:text-brand-400 transition">{{ $event->t('title') }}</h2>
                        <p class="mt-2 text-xs text-surface-400">
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $event->starts_at->isoFormat('D MMMM YYYY · HH:mm') }}
                            </span>
                        </p>
                        <p class="mt-1 text-xs text-surface-400">{{ $event->t('location') }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">{{ $events->links() }}</div>
    @endif
</section>
@endsection
