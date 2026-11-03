<?php
namespace App\Filament\Resources\JobVacancies\Pages;

use App\Filament\Resources\JobVacancies\ImportJobOrder;
use App\Filament\Resources\JobVacancies\JobVacancyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobVacancy extends CreateRecord
{
    protected static string $resource = JobVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [ImportJobOrder::make()];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return JobVacancyResource::completeData($data);
    }
}
