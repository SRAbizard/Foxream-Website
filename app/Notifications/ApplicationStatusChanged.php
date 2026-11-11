<?php

namespace App\Notifications;

use App\Filament\Student\Pages\MyApplications;
use App\Models\Application;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Application $application,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function jobTitle(): string
    {
        return $this->application->jobVacancy?->t('title') ?? __('a position');
    }

    /** Passing the interview gets its own congratulations. */
    private function title(): string
    {
        return $this->newStatus === 'passed'
            ? __('Congratulations, you passed!')
            : __('Application status updated');
    }

    private function body(): string
    {
        return $this->newStatus === 'passed'
            ? __('You passed the selection for ":title". Next, complete your documents while the employment contract is prepared.', ['title' => $this->jobTitle()])
            : __('Your application for ":title" is now ":status".', [
                'title'  => $this->jobTitle(),
                'status' => __('application.status.'.$this->newStatus),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title())
            ->body($this->body())
            ->icon($this->newStatus === 'passed' ? 'heroicon-o-trophy' : 'heroicon-o-briefcase')
            ->iconColor(match ($this->newStatus) {
                'rejected', 'withdrawn' => 'danger',
                'submitted', 'screening_id', 'screening_jp', 'proposed' => 'info',
                'interview' => 'warning',
                default     => 'success',
            })
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label(__('View'))
                    ->url(MyApplications::getUrl(panel: 'student')),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' — '.$this->jobTitle())
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($this->body())
            ->action(__('View application'), MyApplications::getUrl(panel: 'student'));
    }
}
