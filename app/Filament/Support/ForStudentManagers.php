<?php

namespace App\Filament\Support;

/**
 * Admin resources about students: open only to superadmins and to admins
 * given "manage students" under Admin accounts.
 */
trait ForStudentManagers
{
    public static function canAccess(): bool
    {
        return auth()->user()?->canManageStudents() ?? false;
    }
}
