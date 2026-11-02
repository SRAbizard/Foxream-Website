<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A short news item about an application after passing: the contract is
 * ready to sign, the student signed it, the departure is scheduled.
 */
class ApplicationNews extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $icon = 'heroicon-o-document-text',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->iconColor('success')
            ->actions([\Filament\Actions\Action::make('view')->label(__('View'))->url($this->url)])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line($this->body)
            ->action(__('View'), $this->url);
    }
}
