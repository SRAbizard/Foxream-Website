<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class StudentDashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return __('Student Dashboard');
    }

    public function getHeading(): string
    {
        $name = optional(auth()->user())->name ?? __('Student');
        return __('Welcome back, :name', ['name' => $name]);
    }

    public function getSubheading(): ?string
    {
        return __('Your gateway to a career in Japan.');
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Student\Widgets\WelcomeWidget::class,
            \App\Filament\Student\Widgets\UpcomingSchedulesWidget::class,
            \App\Filament\Student\Widgets\JourneyWidget::class,
            \App\Filament\Student\Widgets\RecommendedJobsWidget::class,
            \App\Filament\Student\Widgets\StatsWidget::class,
        ];
    }
}
