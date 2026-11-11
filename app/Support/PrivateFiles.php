<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Candidates' documents (passport, diplomas, certificates…) and their
 * application files (contract, COE, visa, ticket) live on the private
 * disk, storage/app/private, never under the public /storage link. They
 * are opened only through FileController, by the candidate who owns them
 * or an admin who manages students. Filament's own previews use
 * short-lived signed links to the same disk.
 */
class PrivateFiles
{
    public const DISK = 'local';

    /** Folders that hold candidates' private files (same path on either disk). */
    public const FOLDERS = ['students/documents', 'students/applications'];

    public static function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    public static function canOpen(?User $viewer, int $ownerId): bool
    {
        return $viewer !== null && ($viewer->id === $ownerId || $viewer->canManageStudents());
    }

    /**
     * Moves every file in FOLDERS from the public disk to the private one,
     * keeping its path, so the database needs no change. Safe to run again.
     *
     * @return int files moved
     */
    public static function moveFromPublic(): int
    {
        return self::move(Storage::disk('public'), self::disk());
    }

    /** The way back (rolling the migration back). */
    public static function moveToPublic(): int
    {
        return self::move(self::disk(), Storage::disk('public'));
    }

    private static function move(Filesystem $from, Filesystem $to): int
    {
        $moved = 0;
        foreach (self::FOLDERS as $folder) {
            foreach ($from->allFiles($folder) as $path) {
                if (! $to->exists($path)) {
                    $stream = $from->readStream($path);
                    $to->writeStream($path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                if ($to->exists($path) && $to->size($path) === $from->size($path)) {
                    $from->delete($path);
                    $moved++;
                }
            }
            if ($from->allFiles($folder) === []) {
                $from->deleteDirectory($folder);
            }
        }

        return $moved;
    }
}
