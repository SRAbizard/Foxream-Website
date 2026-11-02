<?php

namespace App\Filament\Student\Widgets;

use App\Support\JobMatching;
use Filament\Widgets\Widget;

/** Open jobs in the student's field for their visa target. */
class RecommendedJobsWidget extends Widget
{
    protected string $view = 'filament.student.widgets.recommended-jobs';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->studentProfile?->primary_visa_category_id !== null;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'jobs'     => JobMatching::recommended($user),
            'field'    => \App\Models\JobCategory::where('slug', $user->studentProfile?->desired_job)->first(),
            'boardUrl' => JobMatching::boardUrl($user),
        ];
    }
}
