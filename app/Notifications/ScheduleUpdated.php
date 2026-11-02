<?php

namespace App\Notifications;

use App\Filament\Student\Pages\MyApplications;
use App\Models\ApplicationSchedule;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A screening, interview or kaiwa practice was scheduled, or its date or link changed. */
class ScheduleUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public ApplicationSchedule $schedule,
        public bool $isNew,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function title(): string
    {
        return $this->isNew
            ? __('New schedule: :type', ['type' => $this->schedule->type_label])
            : __('Schedule updated: :type', ['type' => $this->schedule->type_label]);
    }

    private function body(): string
    {
        return collect([
            $this->schedule->application?->jobVacancy?->t('title'),
            $this->schedule->when ?? __('The date will follow.'),
            $this->schedule->meeting_url ? __('Meeting link is ready.') : __('The meeting link will follow.'),
        ])->filter()->implode(' · ');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title())
            ->body($this->body())
            ->icon('heroicon-o-calendar-days')
            ->iconColor('warning')
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label(__('View schedule'))
                    ->url(MyApplications::getUrl(panel: 'student')),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($this->title())
            ->line($this->body());

        if ($this->schedule->notes) {
            $mail->line($this->schedule->notes);
        }

        return $mail->action(__('View schedule'), MyApplications::getUrl(panel: 'student'));
    }
}
