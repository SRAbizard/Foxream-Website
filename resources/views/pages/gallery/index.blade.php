@extends('layouts.app')
@section('title', __('Gallery') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Gallery — :brand'))
@section('og_description', __('Photos and videos from our trainings, events, and student journeys.'))

@section('content')
<x-jp.page-hero
    :eyebrow="__('Documentation')"
    :title="__('Moments from our journey')"
    :subtitle="__('Photos and videos from our trainings, events, and student journeys.')"
    :petals="10" />

<section class="mx-auto max-w-7xl px-6 py-16">
    @if($albums->isEmpty())
        <div class="glass-card p-12 text-center">
            <svg class="h-12 w-12 mx-auto text-surface-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="mt-4 text-surface-300">{{ __('No gallery items published yet.') }}</p>
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($albums as $album)
                <a href="{{ route('gallery.show', $album->slug) }}" wire:navigate
                   class="group block glass-card overflow-hidden hover:border-brand-500/50 transition">
                    <div class="relative aspect-video bg-surface-900 overflow-hidden">
                        @if($album->cover_url)
                            <img src="{{ $album->cover_url }}" alt="{{ $album->t('title') ?? '' }}" loading="lazy"
                                 class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-surface-600">
                                <svg class="h-14 w-14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <span class="absolute top-3 right-3 inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-black/60 text-white backdrop-blur">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $album->items_count }}
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-display text-lg font-bold text-white group-hover:text-brand-400 transition">{{ $album->t('title') }}</h3>
                        @if($album->t('caption'))
                            <p class="mt-2 text-sm text-surface-400 line-clamp-2">{{ $album->t('caption') }}</p>
                        @endif
                        @if($album->taken_at)
                            <p class="mt-3 text-xs text-surface-500">{{ $album->taken_at->translatedFormat('d F Y') }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">{{ $albums->links() }}</div>
    @endif
</section>
@endsection
