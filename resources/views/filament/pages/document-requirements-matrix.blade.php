<x-filament-panels::page>
    @php
        $visas = $this->getVisas();
        $types = $this->getDocumentTypes();
    @endphp

    {{-- Help / instructions card --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5 mb-6">
        <div class="flex items-start gap-3">
            <div class="shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                    {{ __('Click a cell to cycle its state.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Manage the document types themselves under Students → Document Types.') }}
                </p>
                {{-- Legend --}}
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 font-semibold">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z"/></svg>
                        {{ __('Required to apply') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-semibold">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ __('Required after passing') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-400 font-semibold">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
                        {{ __('Optional') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-semibold">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14L14 6M6 6l8 8"/></svg>
                        {{ __('Not needed') }}
                    </span>
                    <span class="text-gray-400 dark:text-gray-500">→ {{ __('cycle on click') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Matrix table --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
        @if ($types->isEmpty())
            <div class="p-12 text-center">
                <p class="text-sm text-gray-500">{{ __('No document types yet. Add some under Students → Document Types.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" style="border-spacing: 0;">
                    <thead>
                        <tr style="background: rgb(249 250 251);" class="dark:!bg-gray-800/60 border-b border-gray-200 dark:border-white/10">
                            <th style="padding: 16px 20px; min-width: 240px; text-align: left;"
                                class="font-semibold text-gray-700 dark:text-gray-200 sticky left-0 bg-gray-50 dark:!bg-gray-800 z-10">
                                <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Document type') }}</p>
                            </th>
                            @foreach ($visas as $visa)
                                @php $counts = $this->countsFor($visa->slug); @endphp
                                <th style="padding: 16px 20px; min-width: 200px;" class="text-center border-l border-gray-200 dark:border-white/10">
                                    <p class="font-bold text-gray-950 dark:text-white text-base">{{ $visa->t('name') }}</p>
                                    <div class="mt-2 inline-flex items-center gap-1 text-[10px] uppercase tracking-wider rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                        <button type="button" wire:click="setRequiredAllForVisa('{{ $visa->slug }}')"
                                                class="font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700">
                                            {{ __('All required') }}
                                        </button>
                                        <span class="text-gray-300 dark:text-gray-600 mx-0.5">·</span>
                                        <button type="button" wire:click="clearVisa('{{ $visa->slug }}')"
                                                class="font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700">
                                            {{ __('Clear') }}
                                        </button>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-bold text-amber-600 dark:text-amber-400">{{ $counts['apply'] }}</span>
                                        {{ __('to apply') }} ·
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $counts['required'] }}</span>
                                        {{ __('after passing') }} ·
                                        <span class="font-bold text-sky-600 dark:text-sky-400">{{ $counts['optional'] }}</span>
                                        {{ __('optional') }}
                                    </p>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($types as $rowIdx => $type)
                            <tr class="{{ $rowIdx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/40 dark:bg-white/[0.02]' }} hover:bg-primary-50/30 dark:hover:bg-primary-500/5 transition border-b border-gray-100 dark:border-white/5 last:border-0">
                                <td style="padding: 14px 20px;" class="sticky left-0 z-10 {{ $rowIdx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/40 dark:bg-gray-900/95' }}">
                                    <div class="flex items-center gap-3">
                                        @if($type->icon)
                                            <x-filament::icon :icon="$type->icon" class="h-5 w-5 text-gray-400 dark:text-gray-500 shrink-0" />
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-950 dark:text-white">{{ $type->t('label') }}</p>
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500" style="font-family: ui-monospace, SFMono-Regular, monospace;">{{ $type->key }}</p>
                                        </div>
                                    </div>
                                </td>
                                @foreach ($visas as $visa)
                                    @php $state = $matrix[$visa->slug][$type->key] ?? null; @endphp
                                    <td style="padding: 14px 20px;" class="text-center border-l border-gray-200/60 dark:border-white/5">
                                        <button type="button"
                                                wire:click="cycle('{{ $visa->slug }}', '{{ $type->key }}')"
                                                title="{{ match ($state) { 'apply' => __('Required to apply — click for After passing'), 'required' => __('Required after passing — click for Optional'), 'optional' => __('Optional — click to remove'), default => __('Not needed — click for Required to apply') } }}"
                                                @class([
                                                    'inline-flex items-center justify-center transition cursor-pointer',
                                                    'rounded-lg font-semibold text-xs uppercase tracking-wider',
                                                    'h-9 min-w-[88px] px-3 gap-1.5',
                                                    'bg-amber-100 text-amber-800 hover:bg-amber-200 ring-1 ring-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:ring-amber-500/30 dark:hover:bg-amber-500/30' => $state === 'apply',
                                                    'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 ring-1 ring-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:ring-emerald-500/30 dark:hover:bg-emerald-500/30' => $state === 'required',
                                                    'bg-sky-100 text-sky-800 hover:bg-sky-200 ring-1 ring-sky-200 dark:bg-sky-500/20 dark:text-sky-300 dark:ring-sky-500/30 dark:hover:bg-sky-500/30' => $state === 'optional',
                                                    'bg-gray-50 text-gray-400 hover:bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800/40 dark:text-gray-600 dark:ring-white/5 dark:hover:bg-gray-700/40' => $state === null,
                                                ])>
                                            @if ($state === 'apply')
                                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z"/></svg>
                                                {{ __('To apply') }}
                                            @elseif ($state === 'required')
                                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                {{ __('After passing') }}
                                            @elseif ($state === 'optional')
                                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
                                                {{ __('Optional') }}
                                            @else
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14L14 6M6 6l8 8"/></svg>
                                                {{ __('—') }}
                                            @endif
                                        </button>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Action buttons --}}
    <div class="mt-6 flex items-center justify-between gap-3 flex-wrap">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('Documents to apply are needed from the start; documents after passing count once they pass the company interview. Optional documents are accepted but do not block.') }}
        </p>
        <div class="flex items-center gap-3">
            <x-filament::button color="gray" wire:click="loadMatrix" type="button" icon="heroicon-o-arrow-path">
                {{ __('Reset changes') }}
            </x-filament::button>
            <x-filament::button wire:click="save" type="button" icon="heroicon-o-check">
                {{ __('Save changes') }}
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
