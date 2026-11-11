<?php

namespace App\Console\Commands;

use App\Mail\AlertMail;
use App\Support\ErrorAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * One zip with everything that cannot be rebuilt from the code: the
 * database and every uploaded file (storage/app/public and private).
 * Encrypted with BACKUP_PASSWORD, written to BACKUP_DISK, and backups
 * older than BACKUP_KEEP_DAYS are removed. Needs only PHP: SQLite is
 * copied with VACUUM INTO, MySQL/MariaDB with mysqldump.
 *
 * Restore: unzip with the password, put database.sqlite back in
 * database/ (or import database.sql), and copy public/ and private/ into
 * storage/app/.
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Back up the database and uploaded files into one encrypted zip';

    public function handle(): int
    {
        $work = storage_path('app/backup-work-'.Str::random(8));
        File::ensureDirectoryExists($work);

        try {
            $zipPath = $this->makeZip($work);
            $name    = Str::slug(config('app.name')).'-'.now()->format('Y-m-d-His').'.zip';
            $disk    = Storage::disk(config('ops.backup.disk'));

            $stream = fopen($zipPath, 'rb');
            $disk->writeStream($name, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (! $disk->exists($name)) {
                throw new RuntimeException("The backup could not be written to the \"".config('ops.backup.disk').'" disk.');
            }

            $removed = $this->removeOld($disk);
            $this->info(sprintf('Backup %s saved (%s)%s.', $name, $this->size($disk->size($name)), $removed ? ", {$removed} old backup(s) removed" : ''));

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error('Backup failed: '.$e->getMessage());
            $this->tellSomeone($e);

            return self::FAILURE;
        } finally {
            File::deleteDirectory($work);
        }
    }

    private function makeZip(string $work): string
    {
        $dbFile = $this->dumpDatabase($work);

        $zipPath = $work.DIRECTORY_SEPARATOR.'backup.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the zip file.');
        }

        $password = config('ops.backup.password');
        if (filled($password)) {
            $zip->setPassword($password);
        }
        $add = function (string $file, string $as) use ($zip, $password): void {
            $zip->addFile($file, $as);
            if (filled($password)) {
                $zip->setEncryptionName($as, ZipArchive::EM_AES_256);
            }
        };

        $add($dbFile, basename($dbFile));
        foreach (['public', 'private'] as $dir) {
            $root = storage_path('app'.DIRECTORY_SEPARATOR.$dir);
            if (! is_dir($root)) {
                continue;
            }
            foreach (File::allFiles($root) as $file) {
                $add($file->getPathname(), $dir.'/'.str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname()));
            }
        }

        $zip->setArchiveComment(config('app.name').' backup, '.now()->toDateTimeString());
        if (! $zip->close()) {
            throw new RuntimeException('Could not finish the zip file.');
        }

        return $zipPath;
    }

    private function dumpDatabase(string $work): string
    {
        $connection = DB::connection();

        switch ($connection->getDriverName()) {
            case 'sqlite':
                $target = $work.DIRECTORY_SEPARATOR.'database.sqlite';
                // A consistent copy while the site keeps running.
                $connection->statement('VACUUM INTO '.$connection->getPdo()->quote($target));

                return $target;

            case 'mysql':
            case 'mariadb':
                $target = $work.DIRECTORY_SEPARATOR.'database.sql';
                $c = $connection->getConfig();
                $result = Process::env(['MYSQL_PWD' => (string) ($c['password'] ?? '')])->timeout(600)->run([
                    'mysqldump', '--single-transaction', '--routines', '--no-tablespaces',
                    '--host='.$c['host'], '--port='.($c['port'] ?? 3306), '--user='.$c['username'],
                    '--result-file='.$target, $c['database'],
                ]);
                if (! $result->successful()) {
                    throw new RuntimeException('mysqldump failed: '.trim($result->errorOutput() ?: $result->output()));
                }

                return $target;

            default:
                throw new RuntimeException('Backups of a '.$connection->getDriverName().' database are not supported.');
        }
    }

    private function removeOld($disk): int
    {
        $prefix  = Str::slug(config('app.name')).'-';
        $cutoff  = now()->subDays(max(1, config('ops.backup.keep_days')))->getTimestamp();
        $removed = 0;

        foreach ($disk->files() as $file) {
            if (str_starts_with($file, $prefix) && str_ends_with($file, '.zip') && $disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                $removed++;
            }
        }

        return $removed;
    }

    private function tellSomeone(Throwable $e): void
    {
        if (blank($to = config('ops.alert_email'))) {
            return;
        }

        rescue(fn () => Mail::to($to)->send(new AlertMail('Backup failed', [
            'Problem' => $e->getMessage(),
            ...ErrorAlert::details($e),
        ])), report: false);
    }

    private function size(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024).' KB';
    }
}
