<?php

namespace App\Filament\Student\Resources\Documents\Pages;

use App\Filament\Student\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DocumentResource::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        \App\Models\AccessLog::record('document_uploaded', \App\Models\DocumentType::labelFor((string) $this->record->type));
    }
}
