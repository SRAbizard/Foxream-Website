<?php
namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Students\Pages\ViewStudent;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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

            DeleteAction::make(),
        ];
    }

    private function moveTo(string $stage): void
    {
        $this->record->update(['status' => $stage, 'reviewed_at' => now(), 'reviewed_by' => auth()->id()]);
        $this->fillForm();
        Notification::make()->title(__('Moved to: :stage', ['stage' => __('application.status.'.$stage)]))->success()->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Stamp the reviewer when status changes from submitted
        if (($data['status'] ?? null) !== 'submitted') {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = auth()->id();
        }
        return $data;
    }
}
