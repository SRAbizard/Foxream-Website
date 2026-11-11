<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a student account for good: the profile, CV data, documents,
 * applications and access history go with it (foreign keys cascade),
 * and so do the uploaded files and notifications.
 */
class AccountRemoval
{
    public static function delete(User $user): void
    {
        abort_if($user->hasAnyRole(['superadmin', 'admin']), 403, 'Admin accounts are managed under Admin accounts.');

        $user->loadMissing('studentProfile', 'studentDocuments', 'applications');
        $photo = $user->studentProfile?->photo_path;
        $files = $user->studentDocuments->pluck('file_path')
            ->merge($user->applications->flatMap(fn ($a) => array_map(fn ($c) => $a->{$c}, \App\Models\Application::FILES)))
            ->filter()->unique()->values()->all();

        DB::transaction(function () use ($user) {
            $user->notifications()->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            $user->delete();
        });

        \App\Support\PrivateFiles::disk()->delete($files);
        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        // Kept under the admin who deleted it, so the history shows who removed which account.
        \App\Models\AccessLog::record('account_deleted', $user->name.' <'.$user->email.'>');
    }
}
