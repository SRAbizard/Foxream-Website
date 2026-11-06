<x-filament-panels::page>
    @php
        $profile = $this->getStudentProfile();
        $hasRequested = $profile && $profile->visa_target_status !== null;
    @endphp

    @if ($hasRequested)
        @php
            $status = $profile->visa_target_status;
            $colors = [
                'pending'   => ['bg' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-700 dark:text-amber-400', 'ring' => 'ring-amber-300/40'],
                'confirmed' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'text' => 'text-emerald-700 dark:text-emerald-400', 'ring' => 'ring-emerald-300/40'],
                'rejected'  => ['bg' => 'bg-rose-50 dark:bg-rose-500/10', 'text' => 'text-rose-700 dark:text-rose-400', 'ring' => 'ring-rose-300/40'],
                'changed'   => ['bg' => 'bg-sky-50 dark:bg-sky-500/10', 'text' => 'text-sky-700 dark:text-sky-400', 'ring' => 'ring-sky-300/40'],
            ];
            $c = $colors[$status] ?? $colors['pending'];
        @endphp

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $c['bg'] }} {{ $c['text'] }} ring-2 {{ $c['ring'] }}">
                    @if($status === 'confirmed')
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    @elseif($status === 'rejected')
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    @else
                        <svg class="h-6 w-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs uppercase tracking-wider font-bold {{ $c['text'] }}">
                        {{ __('visa.target.status.'.$status) }}
                    </p>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">
                        {{ $profile->primaryVisa?->t('name') ?? '—' }}
                    </h2>
                    @if($profile->primaryVisa?->t('description'))
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $profile->primaryVisa->t('description') }}</p>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                        @if ($profile->visa_target_requested_at)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Requested') }}</p>
                                <p class="text-gray-900 dark:text-gray-100">{{ $profile->visa_target_requested_at->isoFormat('lll') }}</p>
                            </div>
                        @endif
                        @if ($profile->visa_target_reviewed_at)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reviewed') }}</p>
                                <p class="text-gray-900 dark:text-gray-100">{{ $profile->visa_target_reviewed_at->isoFormat('lll') }}</p>
                            </div>
                        @endif
                    </div>

                    @if ($profile->visa_target_notes)
                        <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                            <p class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                {{ $status === 'rejected' ? __('Admin reason') : __('Your notes') }}
                            </p>
                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ $profile->visa_target_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($status === 'confirmed')
            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-500/10 ring-1 ring-emerald-200 dark:ring-emerald-500/30 p-5">
                <p class="text-sm text-emerald-800 dark:text-emerald-300">
                    <strong>{{ __('You\'re all set!') }}</strong>
                    {{ __('Head over to "My Documents" to upload the documents required for this visa.') }}
                </p>
                <a href="{{ \App\Filament\Student\Resources\Documents\DocumentResource::getUrl('index') }}"
                   class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    {{ __('Go to My Documents') }} →
                </a>
            </div>
        @endif
    @endif

    {{-- Form: pick / change visa --}}
    <form wire:submit="submit" class="mt-2">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-1">
                {{ $hasRequested ? __('Change visa target') : __('Choose your visa target') }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                @if($hasRequested && $profile->visa_target_status === 'confirmed')
                    {{ __('Switching will require admin approval again. You won\'t lose your uploaded documents.') }}
                @else
                    {{ __('Pick the visa you\'re targeting. An admin will review and confirm.') }}
                @endif
            </p>

            {{ $this->form }}

            <div class="mt-6 flex items-center justify-end gap-3">
                <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                    {{ $hasRequested ? __('Submit new request') : __('Submit request') }}
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
