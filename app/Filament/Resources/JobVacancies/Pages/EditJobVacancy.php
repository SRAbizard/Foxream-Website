<?php
namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\JobVacancyResource;
use App\Support\JobOrder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJobVacancy extends EditRecord
{
    protected static string $resource = JobVacancyResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return JobVacancyResource::completeData($data, $this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            // The PDF is built from the saved record, so save changes first.
            ActionGroup::make([
                Action::make('viewJobOrder')
                    ->label(__('View PDF'))
                    ->icon('heroicon-o-eye')
                    ->url(fn () => route('admin.job-order-pdf', $this->record))
                    ->openUrlInNewTab(),
                Action::make('downloadJobOrder')
                    ->label(__('Download PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('admin.job-order-pdf', [$this->record, 'download' => 1])),
            ])
                ->label(__('Job Order PDF'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->button()
                ->visible(fn () => JobOrder::filled($this->record))
                ->tooltip(__('Generated from the saved Job Order. Save your changes first.')),
            DeleteAction::make(),
        ];
    }
}
