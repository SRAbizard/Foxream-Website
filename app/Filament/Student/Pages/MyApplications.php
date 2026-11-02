<?php

namespace App\Filament\Student\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationNews;
use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * The student's applications, each with its step from screening to
 * departure and the schedules: date and time (WIB and JST) and the
 * meeting link, or "to follow" while the team has not set them. After
 * passing: the contract to sign, COE, visa and the departure.
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

    /** The student uploads the employment contract they signed; the team is told. */
    public function uploadSignedAction(): Action
    {
        return Action::make('uploadSigned')
            ->label(fn (array $arguments) => empty($arguments['again']) ? __('Upload signed contract') : __('Replace'))
            ->icon('heroicon-o-arrow-up-tray')
            ->size('sm')
            ->modalHeading(__('Upload signed contract'))
            ->schema([
                FileUpload::make('file')->label(__('Signed contract'))
                    ->helperText(__('A scan or clear photos of every signed page, as one PDF if you can.'))
                    ->disk('public')->directory('students/applications')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(10240)->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $app = Application::where('user_id', auth()->id())->whereNotNull('contract_path')
                    ->findOrFail($arguments['application'] ?? 0);
                $app->update(['signed_contract_path' => $data['file'], 'signed_contract_at' => now()]);
                \App\Models\AccessLog::record('contract_signed', $app->jobVacancy?->t('title'));

                foreach (User::studentManagers() as $admin) {
                    $admin->notify(new ApplicationNews(
                        __('Signed contract uploaded'),
                        __(':name uploaded the signed contract for ":title".', ['name' => auth()->user()->name, 'title' => $app->jobVacancy?->t('title')]),
                        ApplicationResource::getUrl('edit', ['record' => $app], panel: 'admin'),
                    ));
                }

                Notification::make()->title(__('Signed contract uploaded'))
                    ->body(__('Thank you! Our team will check it and apply for your COE.'))->success()->send();
            });
    }
}
