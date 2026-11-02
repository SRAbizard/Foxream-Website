@php
    $firstName = \Illuminate\Support\Str::of($user?->name ?? 'Admin')->before(' ');
    $busiest = $map->sortByDesc(fn ($r) => count($r['jobs']))->first();
    $pipeMax = max(1, $pipeline->max('count'));
    $countUp = "if (! matchMedia('(prefers-reduced-motion: reduce)').matches && v > 0) { const to = v, t0 = performance.now(); v = 0; const step = (t) => { const p = Math.min(1, (t - t0) / 900); v = Math.round(to * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); }; requestAnimationFrame(step); }";
    $summary = collect([
        $students && $pending['newApps'] ? trans_choice('{1} :count new application|[2,*] :count new applications', $pending['newApps'], ['count' => $pending['newApps']]) : null,
        $students && $pending['docs'] ?trans_choice('{1} :count document to verify|[2,*] :count documents to verify', $pending['docs'], ['count' => $pending['docs']]) : null,
        $pending['messages'] ? trans_choice('{1} :count unread message|[2,*] :count unread messages', $pending['messages'], ['count' => $pending['messages']]) : null,
    ])->filter();
@endphp

<x-filament-widgets::widget>
<div class="pjm">

    {{-- ===== HERO: greeting + quick actions ===== --}}
    <section class="pjm-card pjm-hero">
        <div class="pjm-hero-copy">
            <span class="pjm-sticker">管制室 · {{ __('Mission Control') }}</span>
            <h2 class="pjm-title">{{ $greeting['text'] }}, {{ $firstName }}!</h2>
            <p class="pjm-jp">{{ $greeting['jp'] }} · {{ $today }}</p>
            <p class="pjm-summary">
                @if($summary->isNotEmpty())
                    {{ __('Today on the radar:') }} <b>{{ $summary->join(', ', ' '.__('and').' ') }}</b>.
                @else
                    {{ __('All clear. Nothing is waiting for you right now.') }} 🎌
                @endif
            </p>
        </div>
        <div class="pjm-actions">
            @foreach($actions as $a)
                <a href="{{ $a['url'] }}" @if(! empty($a['external'])) target="_blank" rel="noopener" @endif
                   class="pjm-action {{ ! empty($a['primary']) ? 'is-primary' : '' }}">
                    @svg($a['icon'], 'h-4 w-4 shrink-0')
                    <span>{{ $a['label'] }}</span>
                </a>
            @endforeach
        </div>
        <div class="pjm-hero-sun" aria-hidden="true"></div>
    </section>

    {{-- ===== KPI cards ===== --}}
    <div class="pjm-kpis">
        @foreach($kpis as $k)
            @php
                $n = count($k['spark']);
                $max = max(1, max($k['spark'] ?: [0]));
                $pts = collect($k['spark'])->map(fn ($v, $i) => round($i * (100 / max(1, $n - 1)), 2).','.round(30 - ($v / $max) * 24 - 3, 2))->join(' ');
            @endphp
            <a href="{{ $k['url'] }}" class="pjm-card pjm-kpi tone-{{ $k['tone'] }}">
                <div class="pjm-kpi-top">
                    <span class="pjm-chip">@svg($k['icon'], 'h-5 w-5')</span>
                    @if(! is_null($k['delta']))
                        <span class="pjm-delta {{ $k['delta'] > 0 ? 'up' : ($k['delta'] < 0 ? 'down' : '') }}"
                              title="{{ __(':count this week vs last week', ['count' => $k['thisWeek']]) }}">
                            {{ $k['delta'] > 0 ? '▲ +'.$k['delta'] : ($k['delta'] < 0 ? '▼ '.$k['delta'] : '＝') }}
                        </span>
                    @endif
                </div>
                <b class="pjm-kpi-value" x-data="{ v: {{ $k['value'] }} }" x-init="{{ $countUp }}" x-text="v">{{ $k['value'] }}</b>
                <span class="pjm-kpi-label">{{ $k['label'] }}</span>
                @if($n > 1)
                    <svg class="pjm-spark" viewBox="0 0 100 30" preserveAspectRatio="none" aria-hidden="true">
                        <polygon points="0,30 {{ $pts }} 100,30" class="area"/>
                        <polyline points="{{ $pts }}" class="line"/>
                    </svg>
                    <span class="pjm-spark-cap">{{ __('last 14 days') }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- ===== Pipeline + to-do ===== --}}
    <div class="pjm-row">
        @if($students)
        <section class="pjm-card pjm-pipe">
            <header class="pjm-head">
                <h3>{{ __('Application pipeline') }} <small>応募の流れ</small></h3>
                <span class="pjm-muted">{{ __('Click a stage to open those applications') }}</span>
            </header>
            <ol class="pjm-stages">
                @foreach($pipeline as $i => $s)
                    <li>
                        <a href="{{ $s['url'] }}" class="pjm-stage">
                            <span class="pjm-stage-n">0{{ $i + 1 }}</span>
                            <b x-data="{ v: {{ $s['count'] }} }" x-init="{{ $countUp }}" x-text="v">{{ $s['count'] }}</b>
                            <span class="pjm-stage-label">{{ $s['label'] }}</span>
                            <span class="pjm-bar"><i style="--w: {{ round($s['count'] / $pipeMax * 100) }}%"></i></span>
                        </a>
                    </li>
                @endforeach
            </ol>
            <div class="pjm-exits">
                @foreach($exits as $e)
                    <a href="{{ $e['url'] }}" class="pjm-pill">{{ $e['label'] }} · <b>{{ $e['count'] }}</b></a>
                @endforeach
            </div>
        </section>
        @endif

        <section class="pjm-card pjm-todo">
            <header class="pjm-head"><h3>{{ __('Needs your action') }} <small>やること</small></h3></header>
            <ul>
                @foreach($todos as $t)
                    <li>
                        <a href="{{ $t['url'] }}" class="{{ $t['count'] ? '' : 'is-done' }}">
                            <span class="pjm-check">@if($t['count']) @svg($t['icon'], 'h-4 w-4') @else ✓ @endif</span>
                            <span class="pjm-todo-label">{{ $t['label'] }}</span>
                            <b>{{ $t['count'] ?: __('Done') }}</b>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- ===== Activity chart + job map ===== --}}
    <div class="pjm-row">
        <section class="pjm-card pjm-chart" x-data="{ hover: null }">
            <header class="pjm-head">
                <h3>{{ __('Activity') }} <small>活動</small></h3>
                <div class="pjm-tabs" role="group" aria-label="{{ __('Chart range') }}">
                    @foreach([7, 30, 90] as $d)
                        <button type="button" wire:click="setRange({{ $d }})" aria-pressed="{{ $range === $d ? 'true' : 'false' }}">
                            {{ __(':count days', ['count' => $d]) }}
                        </button>
                    @endforeach
                </div>
            </header>
            <div class="pjm-legend">
                <span><i class="apps"></i>{{ __('Applications') }} · <b>{{ $chartTotals['apps'] }}</b></span>
                <span><i class="regs"></i>{{ __('New candidates') }} · <b>{{ $chartTotals['regs'] }}</b></span>
            </div>
            <div class="pjm-bars" style="--n: {{ $chart->count() }}" wire:loading.class="is-loading">
                @if(! $chartTotals['apps'] && ! $chartTotals['regs'])
                    <p class="pjm-empty">{{ __('No applications or new candidates in this period. Try a longer range.') }}</p>
                @endif
                @foreach($chart as $i => $d)
                    <div class="pjm-col" @mouseenter="hover = {{ $i }}" @mouseleave="hover = null">
                        <i class="apps" style="--h: {{ $d['apps'] / $chartMax * 100 }}%"></i>
                        <i class="regs" style="--h: {{ $d['regs'] / $chartMax * 100 }}%"></i>
                        <span class="pjm-tip" x-show="hover === {{ $i }}" x-cloak>
                            <b>{{ $d['date'] }}</b>
                            {{ __('Applications') }}: {{ $d['apps'] }}<br>{{ __('New candidates') }}: {{ $d['regs'] }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="pjm-axis"><span>{{ $chart->first()['date'] ?? '' }}</span><span>{{ $chart->last()['date'] ?? '' }}</span></div>
        </section>

        <section class="pjm-card pjm-map" x-data="{ sel: @js($busiest['key'] ?? 'kanto'), regions: @js($map) }">
            <header class="pjm-head"><h3>{{ __('Open jobs by region') }} <small>求人マップ</small></h3></header>
            <div class="pjm-map-body">
                <svg viewBox="0 0 400 440" class="pjm-japan" role="group" aria-label="{{ __('Job map of Japan') }}">
                    <path class="land" d="M292 34 Q318 18 344 26 Q372 30 384 54 Q378 80 356 92 Q338 108 314 104 Q294 96 286 74 Q282 50 292 34 Z"/>
                    <path class="land" d="M284 120 Q300 126 310 146 Q314 176 304 204 Q296 232 280 252 Q262 272 236 284 Q206 296 176 310 Q146 324 116 334 Q96 340 84 334 Q78 324 92 316 Q118 304 146 292 Q178 278 206 264 Q232 250 248 228 Q262 204 266 174 Q268 144 284 120 Z"/>
                    <path class="land" d="M132 344 Q158 336 184 336 Q198 342 192 356 Q172 366 146 366 Q130 360 132 344 Z"/>
                    <path class="land" d="M58 342 Q78 340 92 350 Q100 370 92 392 Q80 412 64 410 Q48 402 44 382 Q42 358 58 342 Z"/>
                    @foreach($map as $r)
                        <g class="pin {{ count($r['jobs']) ? '' : 'is-empty' }}" :class="{ 'is-active': sel === '{{ $r['key'] }}' }"
                           tabindex="0" role="button" @click="sel = '{{ $r['key'] }}'" @keydown.enter="sel = '{{ $r['key'] }}'"
                           aria-label="{{ $r['name'] }}: {{ count($r['jobs']) }}">
                            <circle cx="{{ $r['x'] }}" cy="{{ $r['y'] }}" r="{{ count($r['jobs']) ? 9 + min(count($r['jobs']), 6) : 7 }}"/>
                            @if(count($r['jobs']))<text x="{{ $r['x'] }}" y="{{ $r['y'] + 4 }}" text-anchor="middle">{{ count($r['jobs']) }}</text>@endif
                        </g>
                    @endforeach
                </svg>
                <div class="pjm-map-list">
                    <template x-for="r in regions.filter(r => r.key === sel)" :key="r.key">
                        <div>
                            <p class="pjm-region"><b x-text="r.name"></b> <span x-text="r.jp"></span></p>
                            <template x-if="r.jobs.length === 0"><p class="pjm-muted">{{ __('No open jobs in this region yet.') }}</p></template>
                            <ul>
                                <template x-for="j in r.jobs" :key="j.url">
                                    <li><a :href="j.url"><b x-text="j.title"></b><span x-text="j.company + ' · ' + j.city"></span></a></li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </div>

    {{-- ===== Recent activity ===== --}}
    <section class="pjm-card pjm-feed">
        <header class="pjm-head"><h3>{{ __('Recent activity') }} <small>最近の動き</small></h3></header>
        @if($feed->isEmpty())
            <p class="pjm-muted">{{ __('No activity yet.') }}</p>
        @else
            <ul>
                @foreach($feed as $f)
                    <li>
                        <a href="{{ $f['url'] }}">
                            <span class="pjm-chip tone-{{ $f['tone'] }}">@svg($f['icon'], 'h-4 w-4')</span>
                            <span class="pjm-feed-text">{{ $f['text'] }}</span>
                            <time datetime="{{ $f['at']->toIso8601String() }}">{{ $f['at']->diffForHumans() }}</time>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</div>
</x-filament-widgets::widget>
