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

// Demo accounts (…@demo.test) for trying the journey on a laptop; see App\Support\DemoAccounts.
Artisan::command('demo:setup', function () {
    if (! \App\Support\DemoAccounts::enabled()) {
        return $this->error('Only on a local install (APP_ENV=local).');
    }
    [$admin, $student] = \App\Support\DemoAccounts::setUp();
    $this->info("Ready: {$admin->name} ({$admin->email}) and {$student->name} ({$student->email}, CV filled in).");
    $this->line('Switch between them with the "Demo" button at the bottom right of the panels.');
    $this->line('Register more students with an @demo.test email: they show up in the switcher, no email needed.');
    $this->line('Skip typing their CV with: php artisan demo:cv <email>');
})->purpose('Create the demo admin and student for trying the journey locally');

Artisan::command('demo:cv {email}', function (string $email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (! \App\Support\DemoAccounts::enabled() || ! \App\Support\DemoAccounts::isDemo($user)) {
        return $this->error('Only for an @demo.test account on a local install.');
    }
    \App\Support\DemoAccounts::fillCv($user);
    $this->info("The CV of {$user->name} is filled in with sample data.");
})->purpose("Fill in a demo student's CV with sample data, to skip typing it");

Artisan::command('demo:clear', function () {
    $this->info('Removed '.\App\Support\DemoAccounts::clear().' demo account(s) with their applications and files.');
})->purpose('Remove every @demo.test account with its applications and files');
