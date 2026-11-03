<x-filament-panels::page>
    @php
        $applications = $this->getApplications();
        // The steps shown on each pass; contract … departure come after passing.
        $steps = ['submitted', 'screening_id', 'screening_jp', 'interview', 'passed', 'contract', 'coe', 'visa', 'departure'];
    @endphp

    <div class="pja">
        @forelse($applications as $app)
            @php
                $job      = $app->jobVacancy;
                $ended    = in_array($app->status, ['rejected', 'withdrawn'], true);
                $departed = $app->status === 'departed';
                $at       = $departed ? count($steps) : array_search($app->status, $steps, true);
                $next     = $this->nextStep($app);
                $upcoming = $app->schedules->filter->isUpcoming();
                $past     = $app->schedules->reject->isUpcoming();
                $to       = $job?->location_city ?: ($job?->location_prefecture ?: __('Japan'));
            @endphp

            {{-- Each application is a boarding pass from Indonesia to the job's city. --}}
            <article @class(['pja-pass', 'is-ended' => $ended, 'is-departed' => $departed])>
                <div class="pja-main">
                    <p class="pja-top">
                        <span>{{ __('Boarding pass') }} · 搭乗券</span>
                        <span>No. {{ str_pad((string) $app->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </p>

                    <div class="pja-job">
                        <a href="{{ $job ? route('job.show', $job->slug) : '#' }}" class="pja-title">{{ $job?->t('title') ?? '—' }}</a>
                        <p class="pja-meta">
                            {{ $job?->company?->localName() }}
                            @if($job?->visaCategory) <span class="pja-tag">{{ $job->visaCategory->t('name') }}</span> @endif
                        </p>
                    </div>

                    <div class="pja-route" aria-label="{{ __('Indonesia') }} → {{ $to }}">
                        <div><b>IDN</b><span>{{ __('Indonesia') }}</span></div>
                        <i aria-hidden="true"><x-filament::icon icon="heroicon-s-paper-airplane" class="pja-route-plane" /></i>
                        <div class="is-to"><b>JPN</b><span>{{ $to }}</span></div>
                    </div>

                    {{-- The journey: done stops, the plane at the current one, the rest ahead. --}}
                    @unless($ended)
                        <ol class="pja-track" style="--n: {{ count($steps) }}"
                            {{-- On a phone the track scrolls: start with the current step in view. --}}
                            x-data x-init="const now = $el.querySelector('.is-now'); if (now) $el.scrollLeft = now.offsetLeft - ($el.clientWidth - now.offsetWidth) / 2">
                            @foreach($steps as $i => $step)
                                <li @class(['is-done' => $i < $at, 'is-now' => $i === $at])>
                                    <span class="pja-dot">
                                        @if($i < $at) ✓ @elseif($i === $at) <x-filament::icon icon="heroicon-s-paper-airplane" class="h-3.5 w-3.5" /> @else {{ $i + 1 }} @endif
                                    </span>
                                    <span class="pja-step">{{ __('application.status.'.$step) }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endunless

                    @if($next)
                        <div @class(['pja-next', 'is-good' => in_array($app->status, ['passed', 'departure', 'departed'], true), 'is-ended' => $ended])>
                            <p class="pja-next-label">{{ $ended ? __('Notice') : __('Next step') }}</p>
                            <p>{{ $next['text'] }}</p>
                            @if($next['link'])
                                <a href="{{ $next['link'][1] }}" @if($next['link'][2] ?? false) target="_blank" rel="noopener" @endif class="pja-btn">{{ $next['link'][0] }} →</a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- The tear-off stub --}}
                <aside class="pja-stub">
                    {{-- A finished journey gets a passport-style stamp instead of the status sticker. --}}
                    @if($departed || $ended)
                        <span class="pja-stamp">{{ $departed ? __('Departed') : __('application.status.'.$app->status) }}</span>
                    @else
                        <span @class(['pja-status', 'tone-'.(\App\Models\Application::STATUS_COLORS[$app->status] ?? 'gray')])>{{ __('application.status.'.$app->status) }}</span>
                    @endif
                    @unless($ended)
                        <div class="pja-field">
                            <small>{{ __('Step') }}</small>
                            <b>{{ min($at + 1, count($steps)) }}<em>/{{ count($steps) }}</em></b>
                        </div>
                    @endunless
                    <div class="pja-field">
                        <small>{{ __('Applied') }}</small>
                        <strong>{{ $app->created_at->translatedFormat('d M Y') }}</strong>
                    </div>
                </aside>
            </article>

            {{-- After passing: contract, COE, visa, departure --}}
            @if($app->reached('passed') && ! $ended)
                @php
                    $link = fn (string $column, string $label) => $app->{$column}
                        ? '<a href="'.e($app->fileUrl($column)).'" target="_blank" rel="noopener" class="pja-link">'.e($label).' →</a>'
                        : '';
                    $rows = [
                        [
                            'icon'  => 'heroicon-o-document-text',
                            'title' => __('Employment contract'),
                            'done'  => (bool) $app->signed_contract_path,
                            'text'  => match (true) {
                                (bool) $app->signed_contract_path => __('Signed copy uploaded on :date.', ['date' => $app->signed_contract_at?->format('d M Y')]),
                                (bool) $app->contract_path        => __('Your contract is ready. Download it, sign it, then upload the signed copy.'),
                                default                           => __('Being prepared. We will notify you when it is ready to sign.'),
                            },
                            'links' => $link('contract_path', __('Download contract')).' '.$link('signed_contract_path', __('Signed copy')),
                            'sign'  => (bool) $app->contract_path,
                        ],
                        [
                            'icon'  => 'heroicon-o-identification',
                            'title' => 'COE',
                            'done'  => (bool) $app->coe_issued_at,
                            'text'  => $app->coe_issued_at
                                ? __('Issued on :date.', ['date' => $app->coe_issued_at->format('d M Y')])
                                : ($app->reached('contract') ? __('Waiting for the COE to be issued. This usually takes 1 to 3 months.') : __('After the contract is signed.')),
                            'links' => $link('coe_path', __('View COE')),
                        ],
                        [
                            'icon'  => 'heroicon-o-globe-asia-australia',
                            'title' => __('Visa'),
                            'done'  => (bool) $app->visa_issued_at,
                            'text'  => $app->visa_issued_at
                                ? __('Issued on :date.', ['date' => $app->visa_issued_at->format('d M Y')])
                                : ($app->reached('coe') ? __('Our team helps you apply. Keep your passport and documents in My Documents ready.') : __('After the COE is issued.')),
                            'links' => $link('visa_path', __('View visa')),
                        ],
                        [
                            'icon'  => 'heroicon-o-paper-airplane',
                            'title' => __('Departure'),
                            'done'  => $departed,
                            'text'  => $app->departure_at
                                ? $app->departure_at->locale(app()->getLocale())->translatedFormat('l, d M Y · H:i').' WIB'
                                : ($app->reached('visa') ? __('Your departure date and flight ticket will appear here.') : __('After the visa is issued.')),
                            'links' => $link('ticket_path', __('Flight ticket')),
                            'info'  => $app->flight_info,
                        ],
                    ];
                @endphp
                <ul class="pja-papers">
                    @foreach($rows as $row)
                        <li @class(['is-done' => $row['done']])>
                            <span class="pja-chip">
                                @if($row['done']) ✓ @else <x-filament::icon :icon="$row['icon']" class="h-5 w-5" /> @endif
                            </span>
                            <div class="min-w-0">
                                <p class="pja-paper-title">{{ $row['title'] }}</p>
                                <p class="pja-paper-text">{{ $row['text'] }}</p>
                                @if(! empty($row['info']))
                                    <p class="pja-paper-text whitespace-pre-line">{{ $row['info'] }}</p>
                                @endif
                                @if(trim($row['links']) !== '' || ! empty($row['sign']))
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                                        {!! $row['links'] !!}
                                        @if(! empty($row['sign']) && ! $departed)
                                            {{ ($this->uploadSignedAction)(['application' => $app->id, 'again' => (bool) $app->signed_contract_path]) }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Schedules --}}
            @if($upcoming->isNotEmpty())
                <ul class="pja-schedules">
                    @foreach($upcoming as $s)
                        <li>
                            <p class="pja-next-label">{{ __('Upcoming schedule') }} · {{ $s->type_label }}</p>
                            <p class="pja-when">{{ $s->when ?? __('Date and time will follow') }}</p>
                            @if($s->notes)
                                <p class="pja-paper-text whitespace-pre-line">{{ $s->notes }}</p>
                            @endif
                            @if($s->meeting_url)
                                <a href="{{ $s->meeting_url }}" target="_blank" rel="noopener" class="pja-btn is-primary">
                                    <x-filament::icon icon="heroicon-o-video-camera" class="h-4 w-4" />
                                    {{ __('Join meeting') }}
                                </a>
                                <p class="mt-1 break-all text-xs opacity-60">{{ $s->meeting_url }}</p>
                            @else
                                <p class="mt-2 text-xs opacity-70">{{ __('The meeting link will follow. We will notify you.') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($past->isNotEmpty())
                <details class="pja-past">
                    <summary>{{ __('Past schedules') }} ({{ $past->count() }})</summary>
                    <ul>
                        @foreach($past as $s)
                            <li>
                                <span>{{ $s->type_label }} · {{ $s->starts_at?->format('d M Y H:i') ?? '—' }}</span>
                                @if($s->result)
                                    <x-filament::badge :color="$s->result === 'passed' ? 'success' : 'danger'">{{ __('schedule.result.'.$s->result) }}</x-filament::badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @unless($loop->last)
                <hr class="pja-sep">
            @endunless
        @empty
            <div class="pja-empty">
                <x-filament::icon icon="heroicon-o-paper-airplane" class="pja-empty-plane" />
                <p class="pja-title">{{ __('No applications yet') }}</p>
                <p class="pja-paper-text">{{ __('Browse the job board to find your next role.') }}</p>
                <a href="{{ route('job.index') }}" class="pja-btn is-primary">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4" />
                    {{ __('Browse jobs') }}
                </a>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
