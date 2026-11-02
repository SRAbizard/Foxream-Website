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

    /** Open jobs for the student's visa target, their field first, not yet applied for. */
    public static function recommended(User $user, int $limit = 6): Collection
    {
        $profile = $user->studentProfile;
        if (! $profile?->primary_visa_category_id) {
            return collect();
        }

        $field = \App\Models\JobCategory::where('slug', $profile->desired_job)->value('id');

        return JobVacancy::published()
            ->with('company', 'jobCategory')
            ->where(fn ($q) => $q->where('visa_category_id', $profile->primary_visa_category_id)->orWhereNull('visa_category_id'))
            ->whereNotIn('id', $user->applications()->pluck('job_vacancy_id'))
            ->when($field, fn ($q) => $q->orderByRaw('CASE WHEN job_category_id = ? THEN 0 ELSE 1 END', [$field]))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->each(fn ($v) => $v->setAttribute('in_my_field', $field && $v->job_category_id === $field));
    }
}
