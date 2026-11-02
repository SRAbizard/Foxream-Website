<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs uppercase tracking-wider font-semibold text-primary-500">{{ __('Document checklist') }}</p>
                <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">
                    {{ __('You have :verified of :required required documents verified', [
                        'verified' => $progress['verified_count'],
                        'required' => $progress['required_count'],
                    ]) }}
                </h2>

                @if ($progress['using_default'])
                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                        {{ __('Apply to a job first to see the exact required documents for your visa.') }}
                    </p>
                @endif

                <div class="mt-4 max-w-md">
                    <div class="flex items-center justify-between text-xs font-medium mb-1.5">
                        <span class="text-gray-600 dark:text-gray-300">{{ $progress['pct'] }}% {{ __('verified') }}</span>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ count($progress['uploaded_types']) }} {{ __('uploaded') }} · {{ $progress['missing_count'] }} {{ __('missing') }}
                        </span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-primary-500 to-primary-700 transition-all" style="width: {{ $progress['pct'] }}%"></div>
                    </div>
                </div>

                @if ($progress['missing_count'] > 0)
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="text-xs text-gray-500 dark:text-gray-400 mr-1 self-center font-semibold uppercase tracking-wider">{{ __('Still missing:') }}</span>
                        @foreach($progress['missing_types'] as $type)
                            <span class="text-xs px-2 py-1 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 font-medium">
                                {{ \App\Models\DocumentType::labelFor($type) }}
                            </span>
                        @endforeach
                    </div>
                @elseif ($progress['required_count'] > 0)
                    <div class="mt-4 inline-flex items-center gap-2 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ __('All required documents are verified!') }}
                    </div>
                @endif

                @if (! empty($progress['later']))
                    <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">
                        {{ __('Can wait until your employment contract is issued: :docs.', [
                            'docs' => collect($progress['later'])->map(fn ($t) => \App\Models\DocumentType::labelFor($t))->implode(', '),
                        ]) }}
                    </p>
                @endif

                {{-- Optional documents (not blocking, but encouraged) --}}
                @if (! empty($progress['optional']))
                    <div class="mt-5 pt-4 border-t border-gray-200 dark:border-white/5">
                        <p class="text-xs uppercase tracking-wider font-semibold text-sky-700 dark:text-sky-400 mb-2">
                            {{ __('Optional documents') }}
                            <span class="text-gray-500 dark:text-gray-400 font-normal normal-case">
                                — {{ __('strengthen your application but not required') }}
                            </span>
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($progress['optional'] as $type)
                                @php $uploaded = in_array($type, $progress['optional_uploaded_types']); @endphp
                                <span @class([
                                    'text-xs px-2 py-1 rounded-md font-medium inline-flex items-center gap-1',
                                    'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400' => $uploaded,
                                    'bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400' => ! $uploaded,
                                ])>
                                    @if ($uploaded)
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @endif
                                    {{ \App\Models\DocumentType::labelFor($type) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
