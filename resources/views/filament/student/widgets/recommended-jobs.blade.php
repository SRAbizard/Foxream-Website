<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('Jobs for you')"
        :description="$field ? __('Open :field jobs for your visa target.', ['field' => $field->t('name')]) : null">
        @if(! $field)
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Choose the position you are applying for in My Profile to see jobs in your field.') }}</p>
            <a href="{{ \App\Filament\Student\Pages\MyProfile::getUrl() }}" class="mt-3 inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">
                {{ __('My Profile') }} →
            </a>
        @elseif($jobs->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No open :field jobs for your visa target right now. We will add more soon.', ['field' => $field->t('name')]) }}</p>
        @else
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($jobs as $job)
                    <li>
                        <a href="{{ route('job.show', $job->slug) }}" class="block h-full rounded-xl p-4 ring-1 ring-gray-950/5 hover:ring-primary-500 dark:ring-white/10 transition">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white line-clamp-2">{{ $job->t('title') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $job->company?->localName() }}@if($job->location_display) · {{ $job->location_display }}@endif
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ $boardUrl }}" class="mt-4 inline-block text-sm font-semibold text-primary-600 hover:underline">{{ __('See all :field jobs', ['field' => $field->t('name')]) }} →</a>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
