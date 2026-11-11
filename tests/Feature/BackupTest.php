<?php

namespace Tests\Feature;

use App\Mail\AlertMail;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    private string $upload = 'public/backup-test/passport.pdf';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('backups');
        File::ensureDirectoryExists(storage_path('app/public/backup-test'));
        File::put(storage_path('app/'.$this->upload), 'passport scan');
    }

    /**
     * SQLite cannot copy a database inside a transaction, and the other
     * tests each run in one; here the data is kept and the next test
     * starts again from freshly migrated tables.
     */
    public function beginDatabaseTransaction(): void
    {
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/public/backup-test'));
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    /** The one zip on the backups disk, opened. */
    private function openBackup(?string $password = null): ZipArchive
    {
        $files = Storage::disk('backups')->files();
        $this->assertCount(1, $files);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(Storage::disk('backups')->path($files[0])));
        if ($password) {
            $zip->setPassword($password);
        }

        return $zip;
    }

    public function test_the_database_and_uploads_go_into_one_zip(): void
    {
        config(['ops.backup.password' => null]);
        $this->student();

        $this->artisan('backup:run')->assertSuccessful();

        $zip = $this->openBackup();
        $this->assertNotFalse($zip->locateName('database.sqlite'));
        $this->assertSame('passport scan', $zip->getFromName('public/backup-test/passport.pdf'));

        // The copied database really holds the site's data.
        $copy = tempnam(sys_get_temp_dir(), 'db');
        file_put_contents($copy, $zip->getFromName('database.sqlite'));
        $users = (new \PDO('sqlite:'.$copy))->query('select count(*) from users')->fetchColumn();
        $this->assertGreaterThanOrEqual(2, (int) $users);
        @unlink($copy);
    }

    public function test_with_a_password_the_zip_is_encrypted(): void
    {
        config(['ops.backup.password' => 'Backup-secret-123']);

        $this->artisan('backup:run')->assertSuccessful();

        $zip = $this->openBackup();
        $this->assertSame(ZipArchive::EM_AES_256, $zip->statName('public/backup-test/passport.pdf')['encryption_method']);
        $this->assertFalse($zip->getFromName('public/backup-test/passport.pdf'));

        $zip = $this->openBackup('Backup-secret-123');
        $this->assertSame('passport scan', $zip->getFromName('public/backup-test/passport.pdf'));
    }

    public function test_old_backups_are_removed(): void
    {
        config(['ops.backup.keep_days' => 14]);
        $prefix = Str::slug(config('app.name')).'-';
        $disk = Storage::disk('backups');
        $disk->put($old = $prefix.'2020-01-01-020000.zip', 'old');
        $disk->put($other = 'something-else.zip', 'not ours');
        touch($disk->path($old), now()->subDays(20)->getTimestamp());
        touch($disk->path($other), now()->subDays(20)->getTimestamp());

        $this->artisan('backup:run')->assertSuccessful();

        $this->assertFalse($disk->exists($old));
        $this->assertTrue($disk->exists($other));
        $this->assertCount(2, $disk->files());
    }

    public function test_a_failed_backup_is_reported_by_email(): void
    {
        Mail::fake();
        config(['ops.alert_email' => 'ops@example.com', 'ops.backup.disk' => 'no-such-disk']);

        $this->artisan('backup:run')->assertFailed();

        Mail::assertSent(AlertMail::class, fn ($m) => $m->hasTo('ops@example.com') && $m->title === 'Backup failed');
    }

    public function test_the_backup_runs_every_night(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('backup:run')->assertSuccessful();
    }
}
