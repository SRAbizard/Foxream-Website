<?php

namespace App\Filament\Student\Widgets;

use App\Support\JobMatching;
use Filament\Widgets\Widget;

/** Open jobs for the student's visa target, their field first. */
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
        return ['jobs' => JobMatching::recommended(auth()->user())];
    }
}
