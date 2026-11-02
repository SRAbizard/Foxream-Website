<x-filament-widgets::widget>
    <x-filament::section :heading="__('Your road to Japan')" :description="__('Where you are now and what comes next.')">
        <ol class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($steps as $step)
                <li @class([
                    'flex gap-3 rounded-xl p-3 ring-1',
                    'ring-emerald-200 bg-emerald-50/60 dark:ring-emerald-500/20 dark:bg-emerald-500/5' => $step['state'] === 'done',
                    'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-500/10 sm:col-span-2 lg:col-span-3' => $step['state'] === 'current',
                    'ring-gray-950/5 dark:ring-white/10 opacity-70' => $step['state'] === 'upcoming',
                ])>
                    <span @class([
                        'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                        'bg-emerald-500 text-white' => $step['state'] === 'done',
                        'bg-primary-600 text-white' => $step['state'] === 'current',
                        'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $step['state'] === 'upcoming',
                    ])>
                        @if($step['state'] === 'done') ✓ @else {{ $step['n'] }} @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p @class([
                            'text-sm font-semibold',
                            'text-gray-950 dark:text-white' => $step['state'] !== 'upcoming',
                            'text-gray-600 dark:text-gray-400' => $step['state'] === 'upcoming',
                        ])>
                            {{ $step['title'] }}
                            @if($step['state'] === 'current')
                                <span class="ml-1 rounded-md bg-primary-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">{{ __('Now') }}</span>
                            @endif
                        </p>
                        @if($step['hint'])
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $step['hint'] }}</p>
                        @endif
                        @if($step['action'])
                            <a href="{{ $step['action']['url'] }}" class="mt-2 inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700">
                                {{ $step['action']['label'] }} →
                            </a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </x-filament::section>
</x-filament-widgets::widget>
