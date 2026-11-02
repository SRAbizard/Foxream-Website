<?php
namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminUser extends CreateRecord
{
    use SyncsAdminAccess;

    protected static string $resource = AdminUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->takeAccess($data);
    }

    protected function afterCreate(): void
    {
        $this->syncAccess();
    }
}
