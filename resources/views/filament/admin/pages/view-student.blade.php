<x-filament-panels::page>
    @php
        $progress       = $this->getProgress();
        $cv             = $this->getCvCompleteness();
        $student        = $this->record;
        $vp             = $student->studentProfile;
        $vStatus        = $vp?->visa_target_status;
        $vColors = [
            'pending'   => ['bg' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-700 dark:text-amber-400'],
            'confirmed' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'text' => 'text-emerald-700 dark:text-emerald-400'],
            'rejected'  => ['bg' => 'bg-rose-50 dark:bg-rose-500/10', 'text' => 'text-rose-700 dark:text-rose-400'],
            'changed'   => ['bg' => 'bg-sky-50 dark:bg-sky-500/10', 'text' => 'text-sky-700 dark:text-sky-400'],
        ];
        $vc = $vColors[$vStatus] ?? ['bg' => 'bg-gray-50 dark:bg-gray-800', 'text' => 'text-gray-500 dark:text-gray-400'];
    @endphp

    {{-- Visa target card --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="flex items-start gap-4 flex-wrap">
            <div class="shrink-0 inline-flex h-12 w-12 items-center justify-center rounded-xl {{ $vc['bg'] }} {{ $vc['text'] }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">{{ __('Visa target') }}</p>
                @if($vp?->primaryVisa)
                    <h3 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $vp->primaryVisa->t('name') }}</h3>
                    <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider {{ $vc['bg'] }} {{ $vc['text'] }}">
                        @if($vStatus === 'confirmed')
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        @elseif($vStatus === 'pending' || $vStatus === 'changed')
                            <svg class="h-3 w-3 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
                        @endif
                        {{ __('visa.target.status.'.$vStatus) }}
                    </div>
                    @if($vp->visa_target_requested_at)
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Requested') }} {{ $vp->visa_target_requested_at->diffForHumans() }}
                            @if($vp->visa_target_reviewed_at)
                                · {{ __('Reviewed') }} {{ $vp->visa_target_reviewed_at->diffForHumans() }}
                                @if($vp->visaTargetReviewer)
                                    {{ __('by') }} {{ $vp->visaTargetReviewer->name }}
                                @endif
                            @endif
                        </p>
                    @endif
                    @if($vp->visa_target_notes)
                        <div class="mt-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 max-w-2xl">
                            <p class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                {{ $vStatus === 'rejected' ? __('Admin reason') : __('Student notes') }}
                            </p>
                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ $vp->visa_target_notes }}</p>
                        </div>
                    @endif

                    {{-- What the student handed in for this visa --}}
                    @php $handedIn = \App\Support\VisaRequirements::summary($vp); @endphp
                    @if($handedIn)
                        <dl class="mt-4 grid gap-2 sm:grid-cols-2 max-w-3xl">
                            @foreach($handedIn as $line)
                                <div class="rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10 px-3 py-2">
                                    <dt class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">{{ $line['label'] }}</dt>
                                    <dd class="mt-0.5 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $line['value'] ?? '' }}
                                        @if($line['document'])
                                            <a href="{{ $line['document']->file_url }}" target="_blank" class="text-primary-600 hover:underline text-xs whitespace-nowrap">{{ __('View file') }} →</a>
                                            <span class="text-xs text-gray-500">· {{ __('document.status.'.$line['document']->status) }}</span>
                                        @endif
                                        @if($line['warning'])
                                            <span class="block text-xs font-semibold text-amber-600 dark:text-amber-400">⚠ {{ $line['warning'] }}</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                @else
                    <h3 class="mt-1 text-xl font-bold text-gray-400 dark:text-gray-500">{{ __('Not selected yet') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Student has not chosen a visa target. Until they do, document requirements use the default set.') }}</p>
                @endif
            </div>
        </div>

        @if(in_array($vStatus, ['pending', 'changed'], true))
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5 rounded-lg bg-amber-50/50 dark:bg-amber-500/5 -mx-6 -mb-6 px-6 pb-4 pt-3 rounded-b-xl">
                <p class="text-xs text-amber-800 dark:text-amber-300 font-medium">
                    👋 {{ __('This request is awaiting your review. Use the buttons in the page header to confirm or reject.') }}
                </p>
            </div>
        @endif
    </div>

    {{-- Top: stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Documents') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ $progress['verified_count'] }} <span class="text-base text-gray-500 font-normal">/ {{ $progress['required_count'] }}</span>
            </p>
            <div class="mt-3 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-primary-500 to-primary-700" style="width: {{ $progress['pct'] }}%"></div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $progress['pct'] }}% {{ __('verified') }}{{ $progress['using_default'] ? ' · '.__('default set') : '' }}
            </p>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('CV completion') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $cv['pct'] }}%</p>
            <div class="mt-3 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full bg-emerald-500" style="width: {{ $cv['pct'] }}%"></div>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $cv['complete'] ? __('Every CV box is filled') : __('Missing: :items', ['items' => implode(', ', $cv['missing'])]) }}
            </p>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Applications') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $student->applications->count() }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Total submitted') }}</p>
        </div>

        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-5">
            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Japanese level') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ \App\Support\Certificates::japaneseLevel($student->studentProfile?->licenses) ?: '—' }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('From the certificates on the profile') }}</p>
        </div>
    </div>

    {{-- Required documents checklist --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-950 dark:text-white">{{ __('Required documents checklist') }}</h3>
            @if($progress['using_default'])
                <span class="text-xs px-2 py-1 rounded-md bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                    {{ __('Student has not applied yet — using default set') }}
                </span>
            @endif
        </div>

        @if($progress['required_count'] === 0)
            <p class="text-sm text-gray-500">{{ __('No required documents configured for this student\'s visa target.') }}</p>
        @else
            <p class="text-xs uppercase tracking-wider font-semibold text-emerald-700 dark:text-emerald-400 mb-2">{{ __('Required') }} ({{ $progress['required_count'] }})</p>
            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($progress['required'] as $type)
                    @php
                        $verified = in_array($type, $progress['verified_types']);
                        $uploaded = in_array($type, $progress['uploaded_types']);
                    @endphp
                    <li class="flex items-center gap-2 text-sm">
                        @if($verified)
                            <svg class="h-4 w-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="text-gray-900 dark:text-gray-100">{{ \App\Models\DocumentType::labelFor($type) }}</span>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">— {{ __('verified') }}</span>
                        @elseif($uploaded)
                            <svg class="h-4 w-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-gray-900 dark:text-gray-100">{{ \App\Models\DocumentType::labelFor($type) }}</span>
                            <span class="text-xs text-amber-600 dark:text-amber-400">— {{ __('pending review') }}</span>
                        @else
                            <svg class="h-4 w-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-gray-500 dark:text-gray-400">{{ \App\Models\DocumentType::labelFor($type) }}</span>
                            <span class="text-xs text-gray-400">— {{ __('missing') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if (! empty($progress['optional']))
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/5">
                    <p class="text-xs uppercase tracking-wider font-semibold text-sky-700 dark:text-sky-400 mb-2">{{ __('Optional') }} ({{ $progress['optional_count'] }})</p>
                    <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($progress['optional'] as $type)
                            @php $uploaded = in_array($type, $progress['optional_uploaded_types']); @endphp
                            <li class="flex items-center gap-2 text-sm">
                                @if ($uploaded)
                                    <svg class="h-4 w-4 text-sky-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-gray-900 dark:text-gray-100">{{ \App\Models\DocumentType::labelFor($type) }}</span>
                                    <span class="text-xs text-sky-600 dark:text-sky-400">— {{ __('uploaded') }}</span>
                                @else
                                    <svg class="h-4 w-4 text-gray-300 dark:text-gray-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg>
                                    <span class="text-gray-400 dark:text-gray-500">{{ \App\Models\DocumentType::labelFor($type) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>

    {{-- All uploaded documents --}}
    @if($student->studentDocuments->isNotEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="font-semibold text-gray-950 dark:text-white mb-4">{{ __('All uploaded documents') }} ({{ $student->studentDocuments->count() }})</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="text-left py-2 pr-4">{{ __('Type') }}</th>
                            <th class="text-left py-2 pr-4">{{ __('Status') }}</th>
                            <th class="text-left py-2 pr-4">{{ __('Uploaded') }}</th>
                            <th class="text-left py-2 pr-4">{{ __('Verified by') }}</th>
                            <th class="text-left py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($student->studentDocuments as $doc)
                            <tr class="border-b border-gray-100 dark:border-white/5 last:border-0">
                                <td class="py-2 pr-4 text-gray-900 dark:text-gray-100">{{ \App\Models\DocumentType::labelFor($doc->type) }}</td>
                                <td class="py-2 pr-4">
                                    @php $colors = ['pending'=>'amber','verified'=>'emerald','rejected'=>'rose']; $c = $colors[$doc->status] ?? 'gray'; @endphp
                                    <span class="text-xs uppercase tracking-wider font-bold px-2 py-1 rounded-md bg-{{ $c }}-50 text-{{ $c }}-700 dark:bg-{{ $c }}-500/10 dark:text-{{ $c }}-400">
                                        {{ __('document.status.'.$doc->status) }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $doc->created_at->format('d M Y') }}</td>
                                <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $doc->verifier?->name ?? '—' }}</td>
                                <td class="py-2">
                                    <a href="{{ $doc->file_url }}" target="_blank" class="text-primary-600 hover:underline text-xs">{{ __('View') }} →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Applications --}}
    @if($student->applications->isNotEmpty())
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="font-semibold text-gray-950 dark:text-white mb-4">{{ __('Applications') }} ({{ $student->applications->count() }})</h3>
            <ul class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach($student->applications as $app)
                    <li class="py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white truncate">{{ $app->vacancy?->t('title') ?? '—' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $app->vacancy?->company?->name }}
                                @if($app->vacancy?->visaCategory) · <span class="text-primary-600 dark:text-primary-400">{{ $app->vacancy->visaCategory->t('name') }}</span> @endif
                            </p>
                        </div>
                        <div class="text-xs uppercase tracking-wider font-bold px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-800">
                            {{ __('application.status.'.$app->status) }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-panels::page>
