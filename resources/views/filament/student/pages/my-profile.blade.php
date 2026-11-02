<x-filament-panels::page>
    @php $cv = \App\Support\CvCompleteness::for(auth()->user()->fresh()); @endphp
    <div @class([
        'rounded-xl p-4 ring-1 text-sm',
        'bg-emerald-50 ring-emerald-200 text-emerald-800 dark:bg-emerald-500/10 dark:ring-emerald-500/30 dark:text-emerald-300' => $cv['complete'],
        'bg-amber-50 ring-amber-200 text-amber-800 dark:bg-amber-500/10 dark:ring-amber-500/30 dark:text-amber-300' => ! $cv['complete'],
    ])>
        <div class="flex items-center justify-between gap-3">
            <p class="font-semibold">{{ __('Your CV is :pct% complete', ['pct' => $cv['pct']]) }}</p>
            <div class="h-2 w-32 shrink-0 overflow-hidden rounded-full bg-white/70 dark:bg-gray-800">
                <div class="h-full rounded-full {{ $cv['complete'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $cv['pct'] }}%"></div>
            </div>
        </div>
        <p class="mt-1">
            {{ $cv['complete']
                ? __('Every box of your Japanese CV is filled. You can apply for jobs.')
                : __('Still empty: :items. Fill them in and save to apply for jobs.', ['items' => implode(', ', $cv['missing'])]) }}
        </p>
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit">
                {{ __('Save changes') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
