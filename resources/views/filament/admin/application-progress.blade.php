@php
    $app    = $getRecord();
    $steps  = \App\Models\Application::STAGES;
    $at     = array_search($app?->status, $steps, true);
    $ended  = $at === false;
@endphp

{{-- Where the application is. It is moved with the buttons at the top, not edited here. --}}
<div class="pja-admin">
    @if($ended)
        <p class="pja-admin-ended">
            <x-filament::badge :color="\App\Models\Application::STATUS_COLORS[$app?->status] ?? 'gray'">{{ __('application.status.'.$app?->status) }}</x-filament::badge>
            <span>{{ __('This application has ended. To open it again, use "Change step" at the top.') }}</span>
        </p>
    @else
        <ol class="pja-track" style="--n: {{ count($steps) }}"
            x-data x-init="const now = $el.querySelector('.is-now'), go = () => now && ($el.scrollLeft = now.offsetLeft - ($el.clientWidth - now.offsetWidth) / 2); requestAnimationFrame(go); document.fonts?.ready.then(go)">
            @foreach($steps as $i => $step)
                <li @class(['is-done' => $i < $at, 'is-now' => $i === $at])>
                    <span class="pja-dot">@if($i < $at) ✓ @else {{ $i + 1 }} @endif</span>
                    <span class="pja-step">{{ __('application.status.'.$step) }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    @if($app?->reviewed_at)
        <p class="pja-admin-by">
            {{ __('Last moved :when by :name', ['when' => $app->reviewed_at->translatedFormat('d M Y H:i'), 'name' => $app->reviewer?->name ?? '—']) }}
        </p>
    @endif
</div>
