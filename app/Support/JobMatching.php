<?php

namespace App\Support;

use App\Filament\Student\Pages\MyProfile;
use App\Filament\Student\Pages\MyVisaTarget;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Which jobs suit a student, and whether they may apply yet. A student
 * applies once the admin has approved the visa target and the CV is
 * complete, and only for jobs of that visa; jobs in the field they chose
 * on the profile come first in the recommendations.
 */
class JobMatching
{
    /**
     * Why the student cannot apply for this job yet, with a link to fix
     * it, or null when they can.
     *
     * @return array{message: string, action: string, url: string}|null
     */
    public static function blocker(User $user, JobVacancy $vacancy): ?array
    {
        $profile = $user->studentProfile;

        if (! $profile?->hasConfirmedVisa()) {
            return [
                'message' => __('Your visa target must be approved by our team before you can apply.'),
                'action'  => __('Choose a visa target'),
                'url'     => MyVisaTarget::getUrl(panel: 'student'),
            ];
        }

        if ($vacancy->visa_category_id && $vacancy->visa_category_id !== $profile->primary_visa_category_id) {
            return [
                'message' => __('This job is for the :job visa, and your visa target is :mine.', [
                    'job'  => $vacancy->visaCategory?->t('name'),
                    'mine' => $profile->primaryVisa?->t('name'),
                ]),
                'action'  => __('See jobs for my visa'),
                'url'     => route('job.index', ['visa' => $profile->primaryVisa?->slug]),
            ];
        }

        $cv = CvCompleteness::for($user);
        if (! $cv['complete']) {
            return [
                'message' => __('Complete your CV first (:pct% done). Still empty: :items.', [
                    'pct'   => $cv['pct'],
                    'items' => implode(', ', $cv['missing']),
                ]),
                'action'  => __('Complete my CV'),
                'url'     => MyProfile::getUrl(panel: 'student'),
            ];
        }

        return null;
    }

    /**
     * Open jobs in the student's field (the position picked on the
     * profile: Driver Truck → only Driver Truck jobs) for their visa
     * target, not yet applied for. Empty until both are chosen.
     */
    public static function recommended(User $user, int $limit = 6): Collection
    {
        $profile = $user->studentProfile;
        $field   = \App\Models\JobCategory::where('slug', $profile?->desired_job)->value('id');
        if (! $profile?->primary_visa_category_id || ! $field) {
            return collect();
        }

        return JobVacancy::published()
            ->with('company', 'jobCategory')
            ->where('job_category_id', $field)
            ->where(fn ($q) => $q->where('visa_category_id', $profile->primary_visa_category_id)->orWhereNull('visa_category_id'))
            ->whereNotIn('id', $user->applications()->pluck('job_vacancy_id'))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /** The job board filtered to the student's visa and field. */
    public static function boardUrl(User $user): string
    {
        $profile = $user->studentProfile;

        return route('job.index', array_filter([
            'visa'     => $profile?->primaryVisa?->slug,
            'category' => $profile?->desired_job,
        ]));
    }
}
