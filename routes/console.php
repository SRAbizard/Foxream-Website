<?php

use App\Models\StudentEducation;
use App\Models\StudentProfile;
use App\Models\StudentWorkExperience;
use App\Support\JapaneseTranslator;
use App\Support\Rirekisho;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Job titles and majors saved before position_ja / major_ja existed; new ones
// are translated when a profile is saved. High-school streams (IPA, IPS) come
// from Rirekisho::SCHOOL_STREAMS_JA and are skipped.
Artisan::command('cv:translate-history', function () {
    $jobs = [
        [StudentWorkExperience::class, 'position', 'position_ja'],
        [StudentEducation::class, 'major', 'major_ja'],
    ];
    $total = 0;

    foreach ($jobs as [$model, $column, $target]) {
        $todo = $model::whereNull($target)->where($column, '!=', '')
            ->pluck($column)->map(fn ($v) => trim($v))->filter()->unique()
            ->reject(fn ($v) => isset(Rirekisho::SCHOOL_STREAMS_JA[mb_strtoupper($v)]))->values();

        foreach ($todo->chunk(50) as $chunk) {
            $japanese = JapaneseTranslator::many($chunk->values()->all());
            foreach ($chunk->values() as $i => $text) {
                $model::whereNull($target)->whereRaw("TRIM({$column}) = ?", [$text])->update([$target => $japanese[$i]]);
                $this->line("{$text} → {$japanese[$i]}");
            }
        }
        $total += $todo->count();
    }

    // The CV's address line, now translated automatically: only where still empty.
    foreach (StudentProfile::where(fn ($q) => $q->whereNull('address_kana')->orWhere('address_kana', ''))->where(fn ($q) => $q->whereNotNull('city')->orWhereNotNull('province'))->get() as $profile) {
        $address = collect([$profile->city, $profile->province])->map(fn ($v) => trim((string) $v))->filter()->implode(', ');
        if ($address !== '') {
            $profile->update(['address_kana' => JapaneseTranslator::translate($address)]);
            $this->line("{$address} → {$profile->address_kana}");
            $total++;
        }
    }

    $this->info($total ? "Translated {$total} item(s)." : 'Nothing to translate.');
})->purpose('Translate existing job titles, majors and addresses into Japanese for the CV');
