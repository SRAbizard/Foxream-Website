<?php

use App\Models\StudentWorkExperience;
use App\Support\JapaneseTranslator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Job titles saved before position_ja existed; new ones are translated when a profile is saved.
Artisan::command('cv:translate-positions', function () {
    $todo = StudentWorkExperience::whereNull('position_ja')->where('position', '!=', '')
        ->pluck('position')->map(fn ($p) => trim($p))->filter()->unique()->values();
    if ($todo->isEmpty()) {
        return $this->info('Nothing to translate.');
    }

    foreach ($todo->chunk(50) as $chunk) {
        $japanese = JapaneseTranslator::many($chunk->values()->all());
        foreach ($chunk->values() as $i => $position) {
            StudentWorkExperience::whereNull('position_ja')->whereRaw('TRIM(position) = ?', [$position])
                ->update(['position_ja' => $japanese[$i]]);
            $this->line("{$position} → {$japanese[$i]}");
        }
    }
    $this->info("Translated {$todo->count()} job title(s).");
})->purpose('Translate existing work-history job titles into Japanese for the CV');
