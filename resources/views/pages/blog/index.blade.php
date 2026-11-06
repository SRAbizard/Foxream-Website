@extends('layouts.app')
@section('title', __('Blog') . ' — '.\App\Support\Brand::name())
@section('og_title', __(':brand Blog'))
@section('og_description', __('Articles, tips, and alumni stories about working and studying in Japan.'))

@section('content')
<x-jp.page-hero
    page="blog"
    :eyebrow="__('Blog')"
    :title="__('Insights for your Japan journey')"
    :subtitle="__('Articles, tips, and alumni stories — refreshed regularly by the :brand team.')"
    :petals="10" />

{{-- Filters --}}
<section class="mx-auto max-w-7xl px-6 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('blog.index') }}" class="px-3 py-1.5 rounded-full text-xs font-medium {{ ! request('category') ? 'bg-brand-600 text-white' : 'bg-surface-800 text-surface-300 hover:bg-surface-700' }}">
                {{ __('All') }}
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('blog.index', ['category' => $cat->slug]) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-medium {{ request('category') === $cat->slug ? 'bg-brand-600 text-white' : 'bg-surface-800 text-surface-300 hover:bg-surface-700' }}">
                    {{ $cat->t('name') }} <span class="opacity-60">({{ $cat->posts_count }})</span>
                </a>
            @endforeach
        </div>
        <div class="ml-auto">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search articles…') }}"
                   class="rounded-xl border border-surface-700 bg-surface-900/60 px-3.5 py-2 text-sm text-white placeholder-surface-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        </div>
    </form>
</section>

{{-- Posts grid --}}
<section class="mx-auto max-w-7xl px-6 pb-20">
    @if($posts->isEmpty())
        <div class="glass-card p-10 text-center">
            <p class="text-surface-300">{{ __('No articles found.') }}</p>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="glass-card overflow-hidden group hover:border-brand-500/50 transition flex flex-col">
                    @if($post->thumbnail_url)
                        <img src="{{ $post->thumbnail_url }}" alt="" class="aspect-video w-full object-cover">
                    @else
                        <div class="aspect-video w-full bg-gradient-to-br from-brand-700 to-brand-900"></div>
                    @endif
                    <div class="p-5 flex flex-col flex-1">
                        @if($post->category)
                            <span class="inline-block self-start text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $post->category->t('name') }}</span>
                        @endif
                        <h2 class="mt-3 font-display text-lg font-bold text-white group-hover:text-brand-400 transition">{{ $post->t('title') }}</h2>
                        <p class="mt-2 text-sm text-surface-400 line-clamp-3 flex-1">{{ $post->t('excerpt') }}</p>
                        <p class="mt-4 text-xs text-surface-500">{{ optional($post->published_at)->format('d M Y') }}{{ $post->author ? ' · '.$post->author->name : '' }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
