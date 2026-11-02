<x-filament-widgets::widget>
    <x-filament::section :heading="__('Jobs for you')" :description="__('Open jobs for your visa target, your field first.')">
        @if($jobs->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No open jobs for your visa target right now. We will add more soon.') }}</p>
        @else
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($jobs as $job)
                    <li>
                        <a href="{{ route('job.show', $job->slug) }}" class="block h-full rounded-xl p-4 ring-1 ring-gray-950/5 hover:ring-primary-500 dark:ring-white/10 transition">
                            @if($job->in_my_field)
                                <span class="mb-2 inline-block rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ __('Your field') }}</span>
                            @endif
                            <p class="text-sm font-semibold text-gray-950 dark:text-white line-clamp-2">{{ $job->t('title') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $job->company?->name }}@if($job->location_display) · {{ $job->location_display }}@endif
                            </p>
                            @if($job->jobCategory)
                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">{{ $job->jobCategory->t('name') }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
