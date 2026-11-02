<x-filament-widgets::widget>
    <x-filament::section :heading="__('Your upcoming schedule')" icon="heroicon-o-calendar-days">
        <ul class="grid gap-3 md:grid-cols-2">
            @foreach($schedules as $s)
                <li class="rounded-xl p-4 ring-2 ring-primary-500/40 bg-primary-50/50 dark:bg-primary-500/5">
                    <p class="text-xs font-bold uppercase tracking-wider text-primary-700 dark:text-primary-400">{{ $s->type_label }}</p>
                    <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $s->when ?? __('Date and time will follow') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $s->application?->jobVacancy?->t('title') }}</p>
                    @if($s->meeting_url)
                        <a href="{{ $s->meeting_url }}" target="_blank" rel="noopener"
                           class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                            <x-filament::icon icon="heroicon-o-video-camera" class="h-4 w-4" />
                            {{ __('Join meeting') }}
                        </a>
                    @else
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('The meeting link will follow. We will notify you.') }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
