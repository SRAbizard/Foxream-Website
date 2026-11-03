<?php

namespace App\Filament\Resources\JobVacancies;

use App\Support\JobOrderImport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Width;
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
                            ->alpineClickHandler(fn () => self::copyScript(JobOrderImport::prompt())),
                    ]),
                    // For when the browser blocks copying: the admin selects and copies it by hand.
                    Section::make(__('Show the instructions'))->collapsible()->collapsed()->compact()->schema([
                        Textarea::make('prompt')->hiddenLabel()->rows(8)->readOnly()->dehydrated(false)
                            ->default(fn () => JobOrderImport::prompt()),
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
            ->color('gray')
            ->dropdownWidth(Width::ExtraSmall);
    }

    /**
     * Copy the text to the clipboard. The Clipboard API needs https (or
     * localhost) and the browser's permission, so fall back to a hidden
     * textarea, and if that fails too, point to "Show the instructions".
     */
    private static function copyScript(string $text): string
    {
        $done = 'new FilamentNotification().title('.Js::from(__('Instructions copied')).').success().send()';
        $fail = 'new FilamentNotification().title('.Js::from(__('The browser blocked copying')).')'
            .'.body('.Js::from(__('Open "Show the instructions", select all the text and copy it (Ctrl+C).')).').warning().send()';

        return 'const text = '.Js::from($text).';'
            .'(navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject())'
            .'.then(() => '.$done.')'
            .'.catch(() => {'
            .' const t = document.createElement(\'textarea\'); t.value = text; t.style.position = \'fixed\'; t.style.opacity = \'0\';'
            .' $el.parentNode.appendChild(t); t.select();'
            .' let ok = false; try { ok = document.execCommand(\'copy\'); } catch (e) {}'
            .' t.remove(); ok ? '.$done.' : '.$fail.';'
            .'})';
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
                ! $company && blank(data_get($livewire->data, 'company_id')) ? __('Pick the company in the form: the job order does not name a registered one.') : null,
                __('Check every field, then press Save.'),
            ])->filter()->join(' '))
            ->persistent()
            ->send();
    }
}
