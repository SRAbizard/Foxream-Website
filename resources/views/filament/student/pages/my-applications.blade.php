<x-filament-panels::page>
    @php
        $applications = $this->getApplications();
        $academy = $this->getAcademyUrl();
        // The steps shown on each card; contract … departure come after passing.
        $steps = ['submitted', 'screening_id', 'screening_jp', 'interview', 'passed', 'contract', 'coe', 'visa', 'departure'];
    @endphp

    @forelse($applications as $app)
        @php
            $ended   = in_array($app->status, ['rejected', 'withdrawn'], true);
            $at      = array_search($app->status, $steps, true);
            $at      = $app->status === 'departed' ? count($steps) : $at;
            $upcoming = $app->schedules->filter->isUpcoming();
            $past     = $app->schedules->reject->isUpcoming();
        @endphp

        <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 sm:p-6">
            {{-- Job --}}
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ $app->jobVacancy ? route('job.show', $app->jobVacancy->slug) : '#' }}" class="text-lg font-bold text-gray-950 hover:text-primary-600 dark:text-white">
                        {{ $app->jobVacancy?->t('title') ?? '—' }}
                    </a>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $app->jobVacancy?->company?->name }}
                        @if($app->jobVacancy?->visaCategory) · {{ $app->jobVacancy->visaCategory->t('name') }} @endif
                        · {{ __('Applied') }} {{ $app->created_at->format('d M Y') }}
                    </p>
                </div>
                <x-filament::badge :color="\App\Models\Application::STATUS_COLORS[$app->status] ?? 'gray'">
                    {{ __('application.status.'.$app->status) }}
                </x-filament::badge>
            </div>

            {{-- Steps --}}
            @if($ended)
                <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
                    {{ $app->status === 'rejected'
                        ? __('You were not selected for this job. Do not give up: other jobs in your field are waiting.')
                        : __('This application was withdrawn.') }}
                </p>
            @else
                <ol class="mt-5 flex gap-1 overflow-x-auto pb-1">
                    @foreach($steps as $i => $step)
                        <li class="min-w-[5.5rem] flex-1">
                            <div @class([
                                'h-1.5 rounded-full',
                                'bg-emerald-500' => $i < $at,
                                'bg-primary-600' => $i === $at,
                                'bg-gray-200 dark:bg-gray-700' => $i > $at,
                            ])></div>
                            <p @class([
                                'mt-1.5 text-[11px] leading-tight',
                                'font-bold text-primary-700 dark:text-primary-400' => $i === $at,
                                'text-gray-500 dark:text-gray-400' => $i !== $at,
                            ])>{{ __('application.status.'.$step) }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif

            {{-- What to do now --}}
            @if($app->status === 'interview' && ! $ended)
                <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30">
                    {{ __('While you wait for the interview, practise kaiwa with our Indonesia team and in PJI Learning.') }}
                    @if($academy)
                        <a href="{{ $academy }}" target="_blank" rel="noopener" class="font-semibold underline">{{ __('Open PJI Learning') }} →</a>
                    @endif
                </div>
            @elseif($app->reached('passed') && ! $ended && $app->status !== 'departed')
                <div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30">
                    🎉 {{ __('Congratulations, you passed! Complete your documents in My Documents.') }}
                </div>
            @endif

            {{-- Schedules --}}
            @if($upcoming->isNotEmpty())
                <h3 class="mt-5 text-sm font-semibold text-gray-950 dark:text-white">{{ __('Upcoming schedule') }}</h3>
                <ul class="mt-2 grid gap-3 md:grid-cols-2">
                    @foreach($upcoming as $s)
                        <li class="rounded-xl p-4 ring-2 ring-primary-500/40 bg-primary-50/50 dark:bg-primary-500/5">
                            <p class="text-xs font-bold uppercase tracking-wider text-primary-700 dark:text-primary-400">{{ $s->type_label }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $s->when ?? __('Date and time will follow') }}
                            </p>
                            @if($s->notes)
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $s->notes }}</p>
                            @endif
                            @if($s->meeting_url)
                                <a href="{{ $s->meeting_url }}" target="_blank" rel="noopener"
                                   class="mt-3 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                                    <x-filament::icon icon="heroicon-o-video-camera" class="h-4 w-4" />
                                    {{ __('Join meeting') }}
                                </a>
                                <p class="mt-1 break-all text-xs text-gray-500">{{ $s->meeting_url }}</p>
                            @else
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('The meeting link will follow. We will notify you.') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($past->isNotEmpty())
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm text-gray-500 dark:text-gray-400">{{ __('Past schedules') }} ({{ $past->count() }})</summary>
                    <ul class="mt-2 divide-y divide-gray-100 text-sm dark:divide-white/5">
                        @foreach($past as $s)
                            <li class="flex flex-wrap justify-between gap-2 py-2">
                                <span class="text-gray-700 dark:text-gray-200">{{ $s->type_label }} · {{ $s->starts_at?->format('d M Y H:i') ?? '—' }}</span>
                                @if($s->result)
                                    <x-filament::badge :color="$s->result === 'passed' ? 'success' : 'danger'">{{ __('schedule.result.'.$s->result) }}</x-filament::badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </section>
    @empty
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('No applications yet') }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ __('Browse the job board to find your next role.') }}</p>
            <x-filament::button tag="a" :href="route('job.index')" icon="heroicon-o-magnifying-glass" class="mt-4">
                {{ __('Browse jobs') }}
            </x-filament::button>
        </div>
    @endforelse
</x-filament-panels::page>
