@extends('layouts.app')

@section('title', __('Programme Fees') . ' — '.\App\Support\Brand::name())
@section('og_title', __('Programme Fees') . ' — '.\App\Support\Brand::name())
@section('og_description', __('Every official fee, in the open — and how to tell us apart from fraudsters.'))

@section('content')

<x-jp.page-hero
    :eyebrow="__('Transparency')"
    :title="__('Programme fees, in the open.')"
    :subtitle="__('Every official fee is listed here. Anything not on this page or in your written agreement is not an official :brand fee.')"
    :petals="6"
/>

<section class="mx-auto max-w-5xl px-6 pb-20 grid gap-10">
    <div>
        @if($fees)
            <div class="pj-card pj-fee-table">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fees as $f)
                            <tr>
                                <td class="font-bold">{{ $f['name'] }}</td>
                                <td class="whitespace-nowrap">{{ $f['amount'] ?: '—' }}</td>
                                <td>{{ $f['note'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="pj-card p-6 sm:p-8 grid gap-3">
                <h2 class="font-display text-2xl">{{ __('The fee breakdown is being prepared') }}</h2>
                <p class="opacity-80">{{ __('Contact us through an official channel for the latest fees for your programme. We will always confirm them in writing.') }}</p>
                <div><a href="{{ route('contact') }}" wire:navigate class="pj-btn is-red">{{ __('Ask about fees') }} →</a></div>
            </div>
        @endif

        @if($note)
            <div class="prose mt-6 max-w-none">{{ \App\Support\SafeHtml::clean(nl2br(e($note))) }}</div>
        @endif
    </div>

    <x-fraud-notice />
</section>

@endsection
