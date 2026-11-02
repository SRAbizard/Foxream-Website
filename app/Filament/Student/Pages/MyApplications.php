<?php

namespace App\Filament\Student\Pages;

use App\Models\Application;
use App\Support\SiteSettings;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * The student's applications, each with its step from screening to
 * departure and the schedules: date and time (WIB and JST) and the
 * meeting link, or "to follow" while the team has not set them.
 */
class MyApplications extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?int $navigationSort = 30;
    protected string $view = 'filament.student.pages.my-applications';

    public static function getNavigationLabel(): string { return __('My Applications'); }
    public function getTitle(): string                  { return __('My Applications'); }

    public function getApplications(): Collection
    {
        return Application::query()
            ->with('jobVacancy.company', 'jobVacancy.visaCategory', 'schedules')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function getAcademyUrl(): ?string
    {
        return SiteSettings::academyUrl();
    }
}
