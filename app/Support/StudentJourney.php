<?php

namespace App\Support;

use App\Filament\Student\Pages\MyApplications;
use App\Filament\Student\Pages\MyProfile;
use App\Filament\Student\Pages\MyVisaTarget;
use App\Filament\Student\Resources\Documents\DocumentResource;
use App\Models\Application;
use App\Models\User;

/**
 * The candidate's road to Japan in eleven steps, from the account to
 * departure, with where the student is now and what to do next. Steps 5
 * onward follow the furthest active job application.
 */
class StudentJourney
{
    /** Application step => the journey step it stands at (12 = all done). */
    private const APPLICATION_STEP = [
        'submitted'    => 5,
        'screening_id' => 5,
        'screening_jp' => 6,
        'interview'    => 7,
        'passed'       => 8,
        'contract'     => 9,
        'coe'          => 9,
        'visa'         => 10,
        'departure'    => 11,
        'departed'     => 12,
    ];

    /**
     * @return array<int, array{n: int, title: string, state: string, hint: ?string, action: ?array{label: string, url: string}}>
     *         state is done, current or upcoming
     */
    public static function for(User $user): array
    {
        $user->loadMissing('studentProfile.primaryVisa');
        $profile = $user->studentProfile;
        $cv      = CvCompleteness::for($user);
        $apps    = $user->applications()->active()->with('schedules')->get();
        $lead    = $apps->sortByDesc(fn ($a) => self::APPLICATION_STEP[$a->status] ?? 5)->first();
        $atStep  = $lead ? self::APPLICATION_STEP[$lead->status] ?? 5 : null;

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
            4 => $apps->isNotEmpty(),
        ];
        foreach (range(5, 11) as $n) {
            $done[$n] = $atStep !== null && $atStep > $n;
        }
        $current = collect($done)->search(false) ?: null;

        return collect($titles)->map(function ($title, $n) use ($done, $current, $user, $profile, $cv, $lead) {
            [$hint, $action] = $n === $current ? self::next($n, $user, $profile, $cv, $lead) : [null, null];

            return [
                'n'      => $n,
                'title'  => $title,
                'state'  => $done[$n] ? 'done' : ($n === $current ? 'current' : 'upcoming'),
                'hint'   => $hint,
                'action' => $action ? ['label' => $action[0], 'url' => $action[1]] : null,
            ];
        })->values()->all();
    }

    /** What to do at the current step: [hint, [button label, url] or null]. */
    private static function next(int $n, User $user, $profile, array $cv, ?Application $lead): array
    {
        $applications = [__('My Applications'), MyApplications::getUrl(panel: 'student')];
        $schedule = $lead?->schedules->first(fn ($s) => $s->isUpcoming());
        $when = $schedule
            ? __(':type: :when', ['type' => $schedule->type_label, 'when' => $schedule->when ?? __('date to follow')])
            : __('We will let you know the date and the meeting link.');

        return match ($n) {
            2 => match ($profile?->visa_target_status) {
                'pending', 'changed' => [__('Waiting for our team to approve your visa target.'), null],
                'rejected'           => [__('Your request was not approved. See the reason and try again.'), [__('Visa target'), MyVisaTarget::getUrl(panel: 'student')]],
                default              => [__('Choose Tokutei Ginou, Gijinkoku or Internship and send the certificates it needs.'), [__('Choose a visa target'), MyVisaTarget::getUrl(panel: 'student')]],
            },
            3 => [__('Still empty: :items.', ['items' => implode(', ', $cv['missing'])]), [__('Complete my CV'), MyProfile::getUrl(panel: 'student')]],
            4 => [__('Pick a job that matches your visa and field.'), [__('Browse jobs'), JobMatching::boardUrl($user)]],
            5 => [$when, $applications],
            6, 7 => [$when.' '.__('Meanwhile, practise kaiwa with our Indonesia team and in :learning.'), $applications],
            8 => [__('Now upload the rest of the documents for your visa, such as the passport and the MCU.'), [__('My Documents'), DocumentResource::getUrl('index', panel: 'student')]],
            9 => [match (true) {
                    $lead?->signed_contract_path || $lead?->status === 'coe' => __('Your contract is signed. Now we wait for the COE to be issued.'),
                    (bool) $lead?->contract_path => __('Your employment contract is ready: download it, sign it and upload the signed copy.'),
                    default => __('Sign the employment contract when it is issued, then we apply for the COE.'),
                }, $applications],
            10 => [__('Our team helps you apply for the visa. Keep your documents ready.'), $applications],
            11 => [$lead?->departure_at
                    ? __('Departure: :when. See the flight details and your ticket.', ['when' => $lead->departure_at->translatedFormat('l, d M Y · H:i').' WIB'])
                    : __('Your departure date and flight ticket will appear here.'), $applications],
            default => [null, null],
        };
    }
}
