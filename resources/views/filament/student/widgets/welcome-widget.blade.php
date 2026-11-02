<x-filament-widgets::widget>
    <x-filament::section>
        <div class="relative overflow-hidden">
            {{-- Japanese decoration: subtle seigaiha (wave) pattern + sakura branch SVG, top-right --}}
            <div aria-hidden="true" class="pointer-events-none absolute top-0 right-0 h-full w-72 opacity-[0.07] dark:opacity-[0.10]">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="h-full w-full text-primary-600">
                    <defs>
                        <pattern id="seigaiha" x="0" y="0" width="40" height="20" patternUnits="userSpaceOnUse">
                            <path d="M0 20 A 20 20 0 0 1 40 20" fill="none" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M-20 20 A 20 20 0 0 1 20 20" fill="none" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M20 20 A 20 20 0 0 1 60 20" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        </pattern>
                    </defs>
                    <rect width="200" height="200" fill="url(#seigaiha)"/>
                </svg>
            </div>

            {{-- Decorative sakura petals top-right --}}
            <div aria-hidden="true" class="pointer-events-none absolute top-2 right-4 hidden md:block">
                <svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" class="text-pink-300 dark:text-pink-400 opacity-70">
                    {{-- 5-petal sakura blossom --}}
                    <g fill="currentColor">
                        <ellipse cx="32" cy="14" rx="6" ry="11" transform="rotate(0 32 32)"/>
                        <ellipse cx="32" cy="14" rx="6" ry="11" transform="rotate(72 32 32)"/>
                        <ellipse cx="32" cy="14" rx="6" ry="11" transform="rotate(144 32 32)"/>
                        <ellipse cx="32" cy="14" rx="6" ry="11" transform="rotate(216 32 32)"/>
                        <ellipse cx="32" cy="14" rx="6" ry="11" transform="rotate(288 32 32)"/>
                        <circle cx="32" cy="32" r="4" fill="#fbbf24"/>
                    </g>
                </svg>
            </div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-xs uppercase tracking-[0.2em] font-bold text-primary-600 dark:text-primary-400">
                        ようこそ · {{ __('Welcome') }}
                    </p>
                    <h2 class="mt-2 text-2xl md:text-3xl font-bold text-gray-950 dark:text-white tracking-tight">
                        {{ __('Hello, :name', ['name' => $user?->name ?? __('Student')]) }} <span aria-hidden="true">👋</span>
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 max-w-xl leading-relaxed">
                        {{ __('Complete your profile so we can match you with the best opportunities in Japan.') }}
                    </p>

                    <div class="mt-6 max-w-md">
                        <div class="flex items-center justify-between text-xs font-medium mb-2">
                            <span class="text-gray-700 dark:text-gray-200 uppercase tracking-wider">{{ __('CV completion') }}</span>
                            <span class="text-primary-600 dark:text-primary-400 font-bold text-base">{{ $percent }}%</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800 relative">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary-500 via-primary-600 to-primary-700 transition-all duration-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Your CV is :pct% complete', ['pct' => $percent]) }}
                        </p>
                    </div>

                    @unless ($verified)
                        <div class="mt-5 inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Please verify your email address.') }}
                        </div>
                    @endunless
                </div>

                <div class="flex flex-col sm:flex-row md:flex-col gap-3 shrink-0">
                    <a href="{{ $profileUrl }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 hover:shadow-md transition focus:outline-none focus:ring-2 focus:ring-pink-300/60">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        {{ __('Edit profile') }}
                    </a>
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        {{ __('Back to website') }}
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
