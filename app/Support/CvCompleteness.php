<?php

namespace App\Support;

use App\Models\User;

/**
 * How much of the Japanese CV (履歴書) the student's profile fills: the
 * boxes a company looks at, each counted once. Work history, family and
 * notes are left out, since not every student has them.
 */
class CvCompleteness
{
    /**
     * @return array{pct: int, complete: bool, missing: array<int, string>}
     */
    public static function for(User $user): array
    {
        $user->loadMissing('studentProfile', 'educations');
        $p = $user->studentProfile;

        $checks = [
            __('Photo')                              => filled($p?->photo_path),
            __('Full legal name')                    => filled($p?->full_name),
            __('Name in katakana')                   => filled($p?->name_kana),
            __('Gender')                             => filled($p?->gender),
            __('Birth date')                         => filled($p?->birthdate),
            __('Religion')                           => filled($p?->religion),
            __('City & province')                    => filled($p?->city) && filled($p?->province),
            __('Phone')                              => filled($user->phone),
            __('Position applied for')               => filled($p?->desired_job),
            __('Education')                          => $user->educations->contains(fn ($e) => isset(Rirekisho::EDUCATION_LEVELS[$e->level])),
            __('Height & weight')                    => filled($p?->height_cm) && filled($p?->weight_kg),
            __('Blood type')                         => filled($p?->blood_type),
            __('Japanese language certificate')      => Certificates::japaneseLevel($p?->licenses) !== null,
            __('Self introduction in Japanese')      => filled($p?->self_pr_ja),
        ];

        $missing = array_keys(array_filter($checks, fn ($ok) => ! $ok));
        $pct = (int) floor((count($checks) - count($missing)) / count($checks) * 100);

        return ['pct' => $pct, 'complete' => $missing === [], 'missing' => $missing];
    }
}
