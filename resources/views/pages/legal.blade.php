@extends('layouts.app')

@section('title', $title . ' — '.\App\Support\Brand::name())
@section('og_title', $title . ' — '.\App\Support\Brand::name())
@section('og_description', $subtitle)

@section('content')

<x-jp.page-hero :eyebrow="__('Legal')" :title="$title" :subtitle="$subtitle" :petals="6" />

<section class="mx-auto max-w-3xl px-6 pb-20">
    <p class="text-sm opacity-70">{{ __('Last updated: :date', ['date' => $updatedAt->translatedFormat('j F Y')]) }}</p>
    <div class="prose mt-6 max-w-none">
        {{ $body }}
    </div>

    <div class="mt-12 flex flex-wrap gap-3">
        @if($doc === 'privacy')
            <a href="{{ route('legal.terms') }}" wire:navigate class="pj-btn is-red">{{ __('Terms & Conditions') }} →</a>
        @else
            <a href="{{ route('legal.privacy') }}" wire:navigate class="pj-btn is-red">{{ __('Privacy Policy') }} →</a>
        @endif
        <a href="{{ route('contact') }}" wire:navigate class="pj-btn">{{ __('Contact') }}</a>
    </div>
</section>

@endsection
