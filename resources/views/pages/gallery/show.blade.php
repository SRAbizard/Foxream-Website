@extends('layouts.app')
@section('title', $album->t('title') . ' — ' . __('Gallery'))
@section('og_title', $album->t('title') . ' — '.\App\Support\Brand::name())
@section('og_description', $album->t('caption') ?: __('Photos and videos from our trainings, events, and student journeys.'))

@section('content')
<x-jp.page-hero
    page="gallery"
    :eyebrow="__('Documentation')"
    :title="$album->t('title')"
    :subtitle="$album->t('caption')"
    :petals="6" />

<section class="mx-auto max-w-7xl px-6 pt-8 pb-20">
    {{-- Breadcrumb --}}
    <a href="{{ route('gallery.index') }}" wire:navigate
       class="inline-flex items-center gap-1 text-sm text-surface-400 hover:text-brand-400 transition mb-8">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Back to Gallery') }}
    </a>

    @if($album->publishedItems->isEmpty())
        <div class="glass-card p-12 text-center">
            <p class="text-surface-300">{{ __('No items in this album yet.') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($album->publishedItems as $item)
                <button type="button"
                        data-gallery-trigger
                        class="group relative aspect-square overflow-hidden rounded-2xl bg-surface-900 border border-surface-800 hover:border-brand-500/50 transition text-left">
                    @if($item->thumbnail_url)
                        <img src="{{ $item->thumbnail_url }}" alt="{{ $item->t('caption') ?? '' }}"
                             loading="lazy"
                             class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-surface-600">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                    @if($item->type !== 'image')
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/50 transition">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-600/95 text-white shadow-xl group-hover:scale-110 transition">
                                <svg class="h-6 w-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </div>
                    @endif
                    @if($item->t('caption'))
                        <div class="absolute bottom-0 inset-x-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent">
                            <p class="text-xs text-white line-clamp-2">{{ $item->t('caption') }}</p>
                        </div>
                    @endif

                    @php
                        $payload = [
                            'type'    => $item->type,
                            'image'   => $item->image_url,
                            'video'   => $item->video_url,
                            'youtube' => $item->youtube_embed_url,
                            'caption' => $item->t('caption'),
                        ];
                    @endphp
                    <script type="application/json" class="gallery-payload">{!! json_encode($payload) !!}</script>
                </button>
            @endforeach
        </div>
    @endif
</section>

{{-- Lightbox modal --}}
<dialog id="pj-gallery-lightbox" class="bg-transparent backdrop:bg-black/85 backdrop:backdrop-blur-sm p-0 m-0 w-full h-full max-w-full max-h-full">
    <div class="relative w-full h-full flex items-center justify-center p-4 sm:p-10">
        <button type="button" data-close
                class="absolute top-4 right-4 z-10 inline-flex h-10 w-10 items-center justify-center rounded-full bg-surface-900/80 text-white hover:bg-surface-800 backdrop-blur">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="pj-gallery-content" class="w-full max-w-5xl max-h-[90vh] flex flex-col items-center justify-center"></div>
    </div>
</dialog>

@push('head')
<style>
    #pj-gallery-lightbox[open] { display: flex; }
    #pj-gallery-lightbox::backdrop { cursor: pointer; }
</style>
@endpush

<script>
(function () {
    var dialog  = document.getElementById('pj-gallery-lightbox');
    var content = document.getElementById('pj-gallery-content');
    if (! dialog || ! content) return;

    document.querySelectorAll('[data-gallery-trigger]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var payloadEl = btn.querySelector('.gallery-payload');
            if (! payloadEl) return;
            var data;
            try { data = JSON.parse(payloadEl.textContent); } catch (e) { return; }

            var html = '';
            if (data.type === 'image' && data.image) {
                html = '<img src="' + data.image + '" alt="" class="max-h-[80vh] w-auto rounded-2xl shadow-2xl">';
            } else if (data.type === 'video' && data.video) {
                html = '<video src="' + data.video + '" controls autoplay class="max-h-[80vh] w-auto rounded-2xl shadow-2xl bg-black"></video>';
            } else if (data.type === 'youtube' && data.youtube) {
                html = '<div class="w-full aspect-video max-w-4xl"><iframe src="' + data.youtube + '&autoplay=1" class="h-full w-full rounded-2xl" allow="autoplay; encrypted-media" allowfullscreen></iframe></div>';
            }

            if (data.caption) {
                html += '<p class="mt-4 max-w-3xl text-center text-sm text-surface-200">' + data.caption + '</p>';
            }

            content.innerHTML = html;
            dialog.showModal();
        });
    });

    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    dialog.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', function () { dialog.close(); });
    });
    dialog.addEventListener('close', function () { content.innerHTML = ''; });
})();
</script>
@endsection
