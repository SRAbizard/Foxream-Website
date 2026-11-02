<?php

namespace App\Support;

use App\Filament\Student\Pages\MyApplications;
use App\Filament\Student\Pages\MyProfile;
use App\Filament\Student\Pages\MyVisaTarget;
use App\Models\Application;
use App\Models\User;

/**
 * The candidate's road to Japan in eleven steps, from the account to
 * departure, with where the student is now and what to do next. Steps 5
 * onward follow the furthest active job application.
 */
class StudentJourney
{
    /** Application status => the journey step it stands at. */
    private const APPLICATION_STEP = [
        'submitted'           => 5,
        'under_review'        => 5,
        'interview_scheduled' => 7,
        'offered'             => 8,
        'accepted'            => 8,
    ];

    /**
     * @return array<int, array{n: int, title: string, state: string, hint: ?string, action: ?array{label: string, url: string}}>
     *         state is done, current or upcoming
     */
    public static function for(User $user): array
    {
        $user->loadMissing('studentProfile.primaryVisa');
        $profile = $user->studentProfile;
        $visa    = $profile?->visa_target_status;
        $cv      = CvCompleteness::for($user);
        $applied = $user->applications()->active()->pluck('status');
        $atStep  = $applied->map(fn ($s) => self::APPLICATION_STEP[$s] ?? 5)->max();

        $titles = [
            1  => __('Create an account'),
            2  => __('Visa target approved'),
            3  => __('Complete your CV'),
            4  => __('Apply for a job in your field'),
            5  => __('Screening with the Indonesia team'),
            6  => __('Screening with the Japan team'),
            7  => __('Job interview (mensetsu)'),
            8  => __('Passed: complete your documents'),
            9  => __('Employment contract & COE'),
            10 => __('Visa'),
            11 => __('Departure'),
        ];

        // The first step not yet done is the current one.
        $done = [
            1 => true,
            2 => $profile?->hasConfirmedVisa() ?? false,
            3 => $cv['complete'],
            4 => $applied->isNotEmpty(),
        ];
        foreach (range(5, 11) as $n) {
            $done[$n] = $atStep !== null && $atStep > $n;
        }
        $current = collect($done)->search(false) ?: null;

        $hints = [
            2 => match ($visa) {
                'pending', 'changed' => [__('Waiting for our team to approve your visa target.'), null],
                'rejected'           => [__('Your request was not approved. See the reason and try again.'), [__('Visa target'), MyVisaTarget::getUrl(panel: 'student')]],
                default              => [__('Choose Tokutei Ginou, Gijinkoku or Internship and send the certificates it needs.'), [__('Choose a visa target'), MyVisaTarget::getUrl(panel: 'student')]],
            },
            3 => [__('Still empty: :items.', ['items' => implode(', ', $cv['missing'])]), [__('Complete my CV'), MyProfile::getUrl(panel: 'student')]],
            4 => [__('Pick a job that matches your visa and field.'), [__('Browse jobs'), route('job.index', ['visa' => $profile?->primaryVisa?->slug])]],
        ];

        return collect($titles)->map(function ($title, $n) use ($done, $current, $hints, $applied) {
            $state = $done[$n] ? 'done' : ($n === $current ? 'current' : 'upcoming');
            [$hint, $action] = $n === $current ? ($hints[$n] ?? [null, $applied->isNotEmpty() ? [__('My Applications'), MyApplications::getUrl(panel: 'student')] : null]) : [null, null];

            return [
                'n'      => $n,
                'title'  => $title,
                'state'  => $state,
                'hint'   => $hint,
                'action' => $action ? ['label' => $action[0], 'url' => $action[1]] : null,
            ];
        })->values()->all();
    }
}
