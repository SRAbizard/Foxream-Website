<?php

namespace App\Filament\Student\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Student\Resources\Documents\DocumentResource;
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

    /**
     * What happens next on this application, in a sentence, with a link
     * when the student can do something about it.
     *
     * @return array{text: string, link: ?array{0: string, 1: string, 2?: bool}}|null
     */
    public function nextStep(Application $app): ?array
    {
        $documents = [__('My Documents'), DocumentResource::getUrl('index')];
        $academy = $this->getAcademyUrl() ? [__('Open PJI Learning'), $this->getAcademyUrl(), true] : null;

        [$text, $link] = match ($app->status) {
            'submitted'    => [__('We received your application. Our Indonesia team will check your profile and documents soon.'), $documents],
            'screening_id' => [__('Our Indonesia team is checking your profile and documents. Make sure your documents are complete.'), $documents],
            'screening_jp' => [__('Your profile has been sent to the team and the company in Japan, who may screen you more than once. Meanwhile, practise kaiwa with our Indonesia team and in PJI Learning.'), $academy],
            'interview'    => [__('While you wait for the interview, practise kaiwa with our Indonesia team and in PJI Learning.'), $academy],
            'passed'       => [__('Congratulations, you passed! Complete your documents in My Documents.'), $documents],
            'contract', 'coe', 'visa' => [__('Follow the steps below: contract, COE, visa and departure.'), null],
            'departure'    => [__('Get ready to fly! Your departure details are below.'), null],
            'departed'     => [__('Have a safe trip and good luck in Japan! 頑張って!'), null],
            'rejected'     => [__('You were not selected for this job. Do not give up: other jobs in your field are waiting.'), [__('Browse jobs'), route('job.index')]],
            'withdrawn'    => [__('This application was withdrawn.'), null],
            default        => [null, null],
        };

        return $text ? ['text' => $text, 'link' => $link] : null;
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
