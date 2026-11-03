<?php

namespace App\Filament\Resources\JobVacancies;

use App\Support\JobOrderImport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use RuntimeException;

/**
 * "Import from PDF" on the job form: an AI reads a job order in any
 * layout and fills the form, which the admin checks before saving.
 * Automatic with a Gemini API key; otherwise the admin copies our prompt
 * into a Claude or Gemini chat with the PDF and pastes the reply back.
 */
class ImportJobOrder
{
    public static function make(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('importJobOrderAuto')
                ->label(__('Read PDF automatically (Gemini)'))
                ->icon('heroicon-o-sparkles')
                ->visible(fn () => JobOrderImport::geminiEnabled())
                ->modalHeading(__('Import from PDF'))
                ->modalDescription(__('Gemini reads the job order and fills in the form. Nothing is saved until you check it and press Save.'))
                ->modalSubmitActionLabel(__('Read and fill in'))
                ->schema([
                    FileUpload::make('file')->label(__('Job order (PDF or photo)'))
                        ->disk('local')->directory('job-order-imports')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(20480)
                        ->required(),
                ])
                ->action(function (array $data, $livewire, Action $action) {
                    $disk = Storage::disk('local');
                    try {
                        $answer = JobOrderImport::readWithGemini($disk->path($data['file']), $disk->mimeType($data['file']) ?: 'application/pdf');
                        self::fill($livewire, $answer);
                    } catch (RuntimeException $e) {
                        Notification::make()->danger()->title(__('Import failed'))->body($e->getMessage())->send();
                        $action->halt();
                    } finally {
                        $disk->delete($data['file']);
                    }
                }),

            Action::make('importJobOrderPaste')
                ->label(__('Paste AI result (Claude / Gemini)'))
                ->icon('heroicon-o-clipboard-document')
                ->modalHeading(__('Import from PDF'))
                ->modalSubmitActionLabel(__('Fill in the form'))
                ->schema([
                    Text::make(__('1. Copy the instructions. 2. Open Claude or Gemini, attach the job order PDF and paste the instructions. 3. Copy the whole reply and paste it below.')),
                    Actions::make([
                        Action::make('copyPrompt')
                            ->label(__('Copy the instructions'))
                            ->icon('heroicon-o-document-duplicate')
                            ->color('gray')
                            ->alpineClickHandler(fn () => 'window.navigator.clipboard.writeText('.Js::from(JobOrderImport::prompt()).').then(() => '
                                .'new FilamentNotification().title('.Js::from(__('Instructions copied')).').success().send())'),
                    ]),
                    Textarea::make('answer')->label(__('Reply from the AI'))
                        ->placeholder('{ "title": "…", "job_order": { … } }')
                        ->rows(10)
                        ->required(),
                ])
                ->action(function (array $data, $livewire, Action $action) {
                    try {
                        self::fill($livewire, $data['answer']);
                    } catch (RuntimeException $e) {
                        Notification::make()->danger()->title(__('Import failed'))->body($e->getMessage())->send();
                        $action->halt();
                    }
                }),
        ])
            ->label(__('Import from PDF'))
            ->icon('heroicon-o-document-arrow-up')
            ->button()
            ->color('gray');
    }

    /** Put the AI's answer into the form (create or edit page) without saving. */
    private static function fill($livewire, string $answer): void
    {
        ['fields' => $fields, 'company' => $company] = JobOrderImport::toFormData($answer);

        foreach ($fields as $path => $value) {
            data_set($livewire->data, $path, $value);
        }

        Notification::make()->success()
            ->title(__(':count fields filled in from the job order', ['count' => count($fields)]))
            ->body(collect([
                $company ? __('The company ":name" is not registered yet: add it under Companies, or pick it in the form.', ['name' => $company]) : null,
                __('Check every field, then press Save.'),
            ])->filter()->join(' '))
            ->persistent()
            ->send();
    }
}
