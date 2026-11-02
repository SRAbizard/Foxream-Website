<?php

namespace App\Filament\Student\Widgets;

use App\Models\ApplicationSchedule;
use Filament\Widgets\Widget;

/** The student's next screenings, interviews and kaiwa practice, with the meeting link. */
class UpcomingSchedulesWidget extends Widget
{
    protected string $view = 'filament.student.widgets.upcoming-schedules';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return static::schedules()->isNotEmpty();
    }

    private static function schedules()
    {
        return ApplicationSchedule::query()
            ->whereHas('application', fn ($q) => $q->where('user_id', auth()->id())->active())
            ->with('application.jobVacancy')
            ->whereNull('result')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '>', now()->subHours(3)))
            ->orderByRaw('starts_at IS NULL')->orderBy('starts_at')
            ->limit(4)->get();
    }

    protected function getViewData(): array
    {
        return ['schedules' => static::schedules()];
    }
}
