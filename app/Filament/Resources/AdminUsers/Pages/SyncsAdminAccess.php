<?php
namespace App\Filament\Resources\AdminUsers\Pages;

/**
 * Role, "manage students" and the active switch are not mass-assignable
 * user fields: they are taken out of the form data and set after saving.
 */
trait SyncsAdminAccess
{
    private array $access = [];

    private function takeAccess(array $data): array
    {
        $this->access = [
            'role'      => $data['role'] ?? null,
            'students'  => (bool) ($data['manage_students'] ?? false),
            'is_active' => $data['is_active'] ?? null,
        ];
        unset($data['role'], $data['manage_students'], $data['is_active']);

        return $data;
    }

    private function syncAccess(): void
    {
        $user = $this->record;
        $self = $user->is(auth()->user());

        if (! $self) {
            if ($this->access['role']) {
                $user->syncRoles([$this->access['role']]);
            }
            if ($this->access['is_active'] !== null) {
                $user->forceFill(['is_active' => (bool) $this->access['is_active']]);
            }
        }
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        $this->access['students'] && $user->hasRole('admin')
            ? $user->givePermissionTo('manage students')
            : $user->revokePermissionTo('manage students');
    }
}
