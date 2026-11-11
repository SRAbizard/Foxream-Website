<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\StudentDocument;
use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Candidates' documents and application files are private. */
class PrivateFilesTest extends TestCase
{
    private User $owner;

    private StudentDocument $document;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake(PrivateFiles::DISK);

        $this->owner = $this->student();
        PrivateFiles::disk()->put('students/documents/passport.pdf', '%PDF passport');
        PrivateFiles::disk()->put('students/applications/coe.pdf', '%PDF coe');

        $this->document = StudentDocument::query()->forceCreate([
            'user_id' => $this->owner->id, 'type' => 'passport', 'file_path' => 'students/documents/passport.pdf', 'status' => 'pending',
        ]);
        $this->application = Application::query()->forceCreate([
            'user_id' => $this->owner->id, 'job_vacancy_id' => $this->vacancy()->id, 'status' => 'coe', 'coe_path' => 'students/applications/coe.pdf',
        ]);
    }

    public function test_the_candidate_opens_their_own_files(): void
    {
        $this->actingAs($this->owner);

        $this->get($this->document->file_url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame('%PDF passport', $this->get($this->document->file_url)->streamedContent());
        $this->get($this->application->fileUrl('coe_path'))->assertOk();
        $this->get($this->document->file_url.'?download=1')->assertDownload();
    }

    public function test_another_candidate_cannot(): void
    {
        $this->actingAs($this->student());

        $this->get($this->document->file_url)->assertForbidden();
        $this->get($this->application->fileUrl('coe_path'))->assertForbidden();
    }

    public function test_a_guest_must_sign_in(): void
    {
        $this->get($this->document->file_url)->assertRedirect('/dashboard/login');
        $this->get($this->application->fileUrl('coe_path'))->assertRedirect('/dashboard/login');
    }

    public function test_only_admins_who_manage_students_can(): void
    {
        $this->actingAs($this->admin(managesStudents: true))->get($this->document->file_url)->assertOk();
        $this->actingAs($this->admin(managesStudents: false))->get($this->document->file_url)->assertForbidden();
    }

    public function test_only_the_application_file_columns_can_be_asked_for(): void
    {
        $this->actingAs($this->owner)->get("/files/applications/{$this->application->id}/cover_letter")->assertNotFound();
    }

    public function test_files_are_not_under_the_public_link(): void
    {
        $this->assertFalse(Storage::disk('public')->exists('students/documents/passport.pdf'));
        $this->assertStringNotContainsString('/storage/', $this->document->file_url);
        $this->assertStringNotContainsString('/storage/', $this->application->fileUrl('coe_path'));
    }

    public function test_old_public_files_move_to_private_storage(): void
    {
        Storage::disk('public')->put('students/documents/old-ktp.jpg', 'ktp');
        Storage::disk('public')->put('students/applications/old-visa.pdf', 'visa');
        Storage::disk('public')->put('students/photos/face.jpg', 'photo');

        $this->assertSame(2, PrivateFiles::moveFromPublic());

        $this->assertSame('ktp', PrivateFiles::disk()->get('students/documents/old-ktp.jpg'));
        $this->assertSame('visa', PrivateFiles::disk()->get('students/applications/old-visa.pdf'));
        $this->assertFalse(Storage::disk('public')->exists('students/documents/old-ktp.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('students/photos/face.jpg'));   // photos stay public
        $this->assertSame(0, PrivateFiles::moveFromPublic());                             // safe to run again
    }

    public function test_an_upload_from_the_dashboard_lands_in_private_storage(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('student'));
        $this->actingAs($this->owner);

        \Livewire\Livewire::test(\App\Filament\Student\Resources\Documents\Pages\CreateDocument::class)
            ->fillForm([
                'type'      => 'ktp',
                'file_path' => \Illuminate\Http\UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = StudentDocument::where('user_id', $this->owner->id)->where('type', 'ktp')->value('file_path');
        $this->assertStringStartsWith('students/documents/', $path);
        $this->assertTrue(PrivateFiles::disk()->exists($path));
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_deleting_an_application_removes_its_files(): void
    {
        $this->application->delete();

        $this->assertFalse(PrivateFiles::disk()->exists('students/applications/coe.pdf'));
    }
}
