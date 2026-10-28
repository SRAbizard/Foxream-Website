<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Maps a student's profile onto the Passion Japan 履歴書 (Japanese CV):
 * basic information, education & work history, licences, self-PR and
 * other notes. Values follow the agency's filled-in example (e.g.
 * "School　(Major – S1)", "-" for missing contact details) and the
 * dropdown options of the Word template. Used by RirekishoDocx (the
 * Word file) and RirekishoPdf (the PDF preview).
 */
class Rirekisho
{
    /** Rows per table in the template; extra entries add rows. */
    public const ROWS = ['education' => 4, 'work' => 10, 'licenses' => 4];

    /** Education levels a student can enter: senior high school and up. */
    public const EDUCATION_LEVELS = [
        'sma' => 'SMA', 'smk' => 'SMK',
        'd1' => 'D1', 'd2' => 'D2', 'd3' => 'D3', 'd4' => 'D4',
        's1' => 'S1', 's2' => 'S2', 's3' => 'S3',
    ];

    /** Profile nationality (stored in English) → how the CV writes it. */
    public const NATIONALITIES = [
        'Indonesia'   => 'インドネシア',
        'Philippines' => 'フィリピン',
        'Vietnam'     => 'ベトナム',
        'Myanmar'     => 'ミャンマー',
        'Nepal'       => 'ネパール',
        'Thailand'    => 'タイ',
        'Cambodia'    => 'カンボジア',
        'Sri Lanka'   => 'スリランカ',
        'Bangladesh'  => 'バングラデシュ',
        'India'       => 'インド',
        'Mongolia'    => 'モンゴル',
        'Uzbekistan'  => 'ウズベキスタン',
        'Japan'       => '日本',
    ];

    /** Profile religion → the template's 宗教 dropdown values. */
    private const RELIGIONS = [
        'islam'     => 'イスラム教',
        'muslim'    => 'イスラム教',
        'katolik'   => 'カトリック',
        'catholic'  => 'カトリック',
        'kristen'   => 'キリスト教',
        'protestan' => 'キリスト教',
        'christian' => 'キリスト教',
        'hindu'     => 'ヒンドゥー教',
        'buddha'    => '仏教',
        'buddhist'  => '仏教',
        'konghucu'  => '儒教',
    ];

    public static function data(User $user, ?string $desiredJob = null): array
    {
        $user->loadMissing('studentProfile', 'educations', 'workExperiences');
        $p = $user->studentProfile;

        $birth = $p?->birthdate;
        $photo = $p?->photo_path ? storage_path('app/public/'.$p->photo_path) : null;

        return [
            'today'        => now(),
            'name_kana'    => $p?->name_kana,
            'name'         => Str::upper($p?->full_name ?: $user->name),
            'gender'       => match ($p?->gender) { 'male' => '男', 'female' => '女', default => null },
            'nationality'  => self::NATIONALITIES[$p?->nationality ?: 'Indonesia'] ?? $p->nationality,
            'birth'        => $birth,
            'age'          => $birth?->age,
            'desired_job'  => self::desiredJob($desiredJob ?: $p?->desired_job),
            'address_kana' => $p?->address_kana,
            // Only "CITY, PROVINCE", as in the filled-in example.
            'address'      => Str::upper(collect([$p?->city, $p?->province])->filter()->implode(', ')),
            'phone'        => $user->phone ?: '-',
            'email'        => $user->email ?: '-',
            'line'         => $p?->line_id ?: '-',
            'visits'       => [(int) $p?->japan_visits_study, (int) $p?->japan_visits_work, (int) $p?->japan_visits_other],
            'japanese'     => Certificates::japaneseLevel($p?->licenses),
            'other_lang'   => Certificates::otherLanguages($p?->licenses) ?: '-',
            // Medical conditions now go in the free "other notes" (その他) box.
            'healthy'      => true,
            'health_note'  => null,
            'height'       => $p?->height_cm,
            'weight'       => $p?->weight_kg,
            'religion'     => self::religion($p?->religion),
            'blood'        => in_array($p?->blood_type, ['A', 'B', 'O', 'AB'], true) ? $p->blood_type : null,
            'photo'        => $photo && is_file($photo) ? $photo : null,
            'education'    => self::education($user),
            'work'         => self::work($user),
            'licenses'     => self::licenses($p?->licenses),
            'self_pr'      => $p?->self_pr_ja ?: $p?->self_pr,
            'other'        => self::other($p),
        ];
    }

    /** Oldest first, as a 履歴書 lists them: "School　(Major – S1)". */
    private static function education(User $user): array
    {
        return $user->educations
            ->filter(fn ($e) => isset(self::EDUCATION_LEVELS[$e->level]))
            ->sortBy(fn ($e) => sprintf('%04d%02d', $e->start_year ?? $e->end_year ?? 0, $e->start_month ?? 0))
            ->map(function ($e) {
                $level  = self::EDUCATION_LEVELS[$e->level];
                $detail = collect([$e->major, $level])->filter()->implode(' – ');

                return [
                    'from_y' => $e->start_year, 'from_m' => $e->start_month,
                    'to_y'   => $e->is_current ? '在学中' : $e->end_year, 'to_m' => $e->is_current ? null : $e->end_month,
                    'text'   => $e->institution.($detail !== '' ? '　('.$detail.')' : ''),
                ];
            })->values()->all();
    }

    /** "Company　(Position)", oldest first. */
    private static function work(User $user): array
    {
        return $user->workExperiences->sortBy(fn ($w) => $w->start_date?->timestamp ?? 0)->map(fn ($w) => [
            'from_y' => $w->start_date?->year, 'from_m' => $w->start_date?->month,
            'to_y'   => $w->is_current ? '在職中' : $w->end_date?->year, 'to_m' => $w->is_current ? null : $w->end_date?->month,
            'text'   => $w->company.($w->position ? '　('.$w->position.')' : ''),
        ])->values()->all();
    }

    /** The certificates picked on the profile, oldest first; undated ones last. */
    private static function licenses(?array $licenses): array
    {
        return collect($licenses ?? [])
            ->map(fn ($l) => ['y' => $l['year'] ?? null, 'm' => $l['month'] ?? null, 'text' => Certificates::cvText($l)])
            ->filter(fn ($l) => filled($l['text']))
            ->sortBy(fn ($l) => sprintf('%04d%02d', $l['y'] ? (int) $l['y'] : 9999, (int) $l['m']))
            ->values()->all();
    }

    /** A field slug becomes its Japanese name; older free-text entries print as typed. */
    private static function desiredJob(?string $job): ?string
    {
        if (blank($job)) {
            return null;
        }

        return \App\Models\JobCategory::where('slug', $job)->first()?->t('name', 'ja') ?? $job;
    }

    /**
     * The student's own notes, then the habits they did not tick, as in the
     * agency's example ("お酒は飲みません。"); skipped when the notes already
     * mention them.
     */
    private static function other(?\App\Models\StudentProfile $p): string
    {
        $notes  = trim((string) $p?->cv_other);
        $habits = collect([
            ! $p?->smoker && ! Str::contains($notes, ['タバコ', 'たばこ', '煙草', '喫煙']) ? 'タバコは吸いません。' : null,
            ! $p?->drinker && ! Str::contains($notes, ['お酒', '酒', '飲酒']) ? 'お酒は飲みません。' : null,
        ])->filter()->implode('');

        return collect([$notes, $habits])->filter()->implode("
");
    }

    private static function religion(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }
        foreach (self::RELIGIONS as $needle => $ja) {
            if (Str::contains(Str::lower($value), $needle)) {
                return $ja;
            }
        }

        return null; // not one of the template's options
    }

    public static function filename(User $user, string $ext = 'pdf'): string
    {
        $name = $user->studentProfile?->full_name ?: $user->name;

        return 'PASSION JAPAN INDONESIA_履歴書_'.Str::upper($name).'.'.$ext;
    }

    /** [year, month, day] for the "年 月 日 現在" line. */
    public static function dateParts(?Carbon $date): array
    {
        return $date ? [$date->year, $date->month, $date->day] : [null, null, null];
    }
}
