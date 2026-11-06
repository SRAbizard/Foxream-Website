@extends('layouts.app')
@section('title', $post->t('title') . ' — '.\App\Support\Brand::name())
@section('og_type', 'article')
@section('og_title', $post->t('seo_title') ?: $post->t('title'))
@section('og_description', $post->t('seo_description') ?: $post->t('excerpt'))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post->t('title'),
    'description' => $post->t('excerpt'),
    'datePublished' => optional($post->published_at)->toAtomString(),
    'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? \App\Support\Brand::name()],
    'image' => $post->thumbnail_url ?: asset('images/brand/og.png'),
    'publisher' => ['@type' => 'Organization', 'name' => \App\Support\Brand::company(), 'logo' => ['@type' => 'ImageObject', 'url' => \App\Support\Brand::logo('stacked')]],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-6 pt-14 pb-20">
    <a href="{{ route('blog.index') }}" class="text-sm text-surface-400 hover:text-brand-400 inline-flex items-center gap-1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Back to Blog') }}
    </a>

    @if($post->category)
        <span class="mt-6 inline-block text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $post->category->t('name') }}</span>
    @endif

    <h1 class="mt-3 font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">{{ $post->t('title') }}</h1>

    <p class="mt-4 text-sm text-surface-400">
        {{ optional($post->published_at)->isoFormat('ll') }}{{ $post->author ? ' · '.$post->author->name : '' }}
    </p>

    @if($post->thumbnail_url)
        <img src="{{ $post->thumbnail_url }}" alt="" class="mt-8 w-full aspect-video object-cover rounded-2xl">
    @endif

    <div class="prose prose-invert prose-headings:font-display prose-headings:text-white prose-a:text-brand-400 mt-10 max-w-none text-surface-200 leading-relaxed">
        {{ \App\Support\SafeHtml::clean($post->t('body')) }}
    </div>

    @if(! empty($post->tags))
        <div class="mt-10 flex flex-wrap gap-2">
            @foreach($post->tags as $tag)
                <span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-surface-800 text-surface-300">#{{ $tag }}</span>
            @endforeach
        </div>
    @endif
</article>

@if($related->isNotEmpty())
<section class="mx-auto max-w-7xl px-6 pb-20">
    <h2 class="font-display text-2xl font-bold text-white">{{ __('Related articles') }}</h2>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        @foreach($related as $r)
            <a href="{{ route('blog.show', $r->slug) }}" class="glass-card p-5 hover:border-brand-500/50 transition">
                @if($r->category)<span class="text-[10px] uppercase tracking-wider font-semibold px-2 py-1 rounded-md bg-brand-600/15 text-brand-300">{{ $r->category->t('name') }}</span>@endif
                <h3 class="mt-3 font-semibold text-white">{{ $r->t('title') }}</h3>
                <p class="mt-2 text-sm text-surface-400 line-clamp-2">{{ $r->t('excerpt') }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif
@endsection
