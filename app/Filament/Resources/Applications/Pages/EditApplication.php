<?php
namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    public function getSubheading(): ?string
    {
        return $this->record->user?->name.' → '.$this->record->vacancy?->t('title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('student')->label(__('Student profile'))->icon('heroicon-o-user')->color('gray')
                ->url(fn () => ViewStudent::getUrl(['record' => $this->record->user_id])),

            // One click to the next step; the student is notified.
            Action::make('nextStage')
                ->label(fn () => __('Move to: :stage', ['stage' => __('application.status.'.$this->record->nextStage())]))
                ->icon('heroicon-o-arrow-right-circle')->color('success')
                ->visible(fn () => $this->record->nextStage() !== null)
                ->requiresConfirmation()
                ->modalDescription(__('The student is notified of the new step.'))
                ->action(fn () => $this->moveTo($this->record->nextStage())),

            Action::make('reject')->label(__('Not selected'))->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => ! in_array($this->record->status, ['rejected', 'withdrawn', 'departed'], true))
                ->schema([
                    Textarea::make('reason')->label(__('Note for the team (not shown to the student)'))->rows(3),
                ])
                ->action(function (array $data) {
                    if (filled($data['reason'] ?? null)) {
                        $this->record->admin_notes = trim($this->record->admin_notes."\n".$data['reason']);
                    }
                    $this->moveTo('rejected');
                }),

            ActionGroup::make([
                // For corrections: a step back, a skipped step, withdrawn, or reopening an ended application.
                Action::make('changeStage')->label(__('Change step'))->icon('heroicon-o-arrows-right-left')
                    ->modalDescription(__('For corrections, such as going back a step or reopening an application. The student is notified of the new step.'))
                    ->modalSubmitActionLabel(__('Move'))
                    ->schema([
                        Select::make('status')->label(__('Move to'))
                            ->options(fn () => collect(Application::STATUSES)
                                ->reject(fn ($s) => $s === $this->record->status)
                                ->mapWithKeys(fn ($s) => [$s => __('application.status.'.$s)]))
                            ->required()
                            ->native(false),
                    ])
                    ->action(fn (array $data) => $this->moveTo($data['status'])),

                DeleteAction::make()->visible(fn () => auth()->user()?->hasRole('superadmin')),
            ])->tooltip(__('More')),
        ];
    }

    private function moveTo(string $stage): void
    {
        $this->record->update(['status' => $stage, 'reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
        $this->fillForm();
        Notification::make()->title(__('Moved to: :stage', ['stage' => __('application.status.'.$stage)]))->success()->send();
    }
}
