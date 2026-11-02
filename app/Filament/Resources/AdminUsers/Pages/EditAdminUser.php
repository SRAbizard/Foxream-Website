<?php
namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Resources\Pages\EditRecord;

class EditAdminUser extends EditRecord
{
    use SyncsAdminAccess;

    protected static string $resource = AdminUserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data + [
            'role'            => $this->record->hasRole('superadmin') ? 'superadmin' : 'admin',
            'manage_students' => $this->record->hasDirectPermission('manage students'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->takeAccess($data);
    }

    protected function afterSave(): void
    {
        $this->syncAccess();
    }
}
