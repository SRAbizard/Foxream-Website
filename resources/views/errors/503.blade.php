<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 · {{ __('We\'ll be back soon') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-surface-950 text-surface-100 antialiased flex items-center justify-center font-sans">
    <main class="max-w-2xl px-6 text-center">
        <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600/20 text-brand-400 mb-8">
            <svg class="h-8 w-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="font-display text-7xl font-extrabold text-brand-500">503</p>
        <h1 class="mt-4 font-display text-3xl font-bold text-white">{{ __('We\'ll be back soon') }}</h1>
        <p class="mt-4 text-surface-300">
            {{ __(':brand is undergoing scheduled maintenance. Please check back in a few minutes.') }}
        </p>
        <p class="mt-8 text-xs text-surface-500">{{ \App\Support\Brand::company() }} · {{ date('Y') }}</p>
    </main>
</body>
</html>
