<?php

namespace Tests\Feature;

use App\Filament\Student\Pages\EditProfile;
use App\Filament\Student\Pages\MyApplications;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationNews;
use App\Notifications\ApplicationStatusChanged;
use App\Support\PrivateFiles;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** What candidates and admins can do on their own: withdraw, reset a password, delete an account. */
class SelfServiceTest extends TestCase
{
    private function application(User $student, string $status = 'screening_jp'): Application
    {
        return Application::query()->forceCreate([
            'user_id' => $student->id, 'job_vacancy_id' => $this->vacancy()->id, 'status' => $status,
        ]);
    }

    public function test_a_candidate_withdraws_an_application_and_the_team_is_told(): void
    {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('student'));
        $manager = $this->admin();
        $student = $this->student();
        $app = $this->application($student);

        $this->actingAs($student);
        Livewire::test(MyApplications::class)
            ->callAction('withdraw', data: ['reason' => 'Saya diterima di tempat lain.'], arguments: ['application' => $app->id])
            ->assertHasNoActionErrors();

        $app->refresh();
        $this->assertSame('withdrawn', $app->status);
        $this->assertStringContainsString('Saya diterima di tempat lain.', $app->admin_notes);
        Notification::assertSentTo($manager, ApplicationNews::class);
        Notification::assertNotSentTo($student, ApplicationStatusChanged::class);
    }

    public function test_once_proposed_to_the_company_a_candidate_cannot_withdraw_alone(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('student'));
        $student = $this->student();
        $app = $this->application($student, 'proposed');

        $this->actingAs($student);
        Livewire::test(MyApplications::class)
            ->callAction('withdraw', arguments: ['application' => $app->id]);

        $this->assertSame('proposed', $app->fresh()->status);
        foreach (['proposed', 'interview', 'passed', 'contract', 'coe', 'visa', 'departure', 'departed'] as $status) {
            $this->assertFalse($app->forceFill(['status' => $status])->canBeWithdrawn(), $status);
        }
        foreach (['submitted', 'screening_id', 'screening_jp'] as $status) {
            $this->assertTrue($app->forceFill(['status' => $status])->canBeWithdrawn(), $status);
        }
    }

    public function test_proposed_comes_right_after_the_japan_screening(): void
    {
        $app = $this->application($this->student(), 'screening_jp');

        $this->assertSame('proposed', $app->nextStage());
        $this->assertSame('interview', $app->forceFill(['status' => 'proposed'])->nextStage());
    }

    public function test_a_candidate_cannot_withdraw_someone_elses_application(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('student'));
        $app = $this->application($this->student());

        $this->actingAs($this->student());
        Livewire::test(MyApplications::class)
            ->callAction('withdraw', arguments: ['application' => $app->id]);

        $this->assertSame('screening_jp', $app->fresh()->status);
    }

    public function test_an_admin_can_reset_a_forgotten_password(): void
    {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = $this->admin();

        $this->get('/admin/login')->assertSee('/admin/password-reset/request', false);
        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $admin->email])
            ->call('request');

        Notification::assertSentTo($admin, ResetPassword::class);
    }

    public function test_a_candidate_deletes_their_account_with_everything_in_it(): void
    {
        Storage::fake(PrivateFiles::DISK);
        Filament::setCurrentPanel(Filament::getPanel('student'));
        $student = $this->student();
        PrivateFiles::disk()->put('students/documents/ktp.pdf', 'ktp');
        $student->studentDocuments()->forceCreate(['type' => 'ktp', 'file_path' => 'students/documents/ktp.pdf', 'status' => 'pending']);
        $this->application($student, 'submitted');

        $this->actingAs($student);
        Livewire::test(EditProfile::class)
            ->callAction('deleteMyAccount', data: ['password' => 'password'])
            ->assertHasNoActionErrors()
            ->assertRedirect(url('/'));

        $this->assertNull(User::find($student->id));
        $this->assertSame(0, Application::where('user_id', $student->id)->count());
        $this->assertFalse(PrivateFiles::disk()->exists('students/documents/ktp.pdf'));
        $this->assertGuest();
    }

    public function test_deleting_needs_the_right_password(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('student'));
        $student = $this->student();

        $this->actingAs($student);
        Livewire::test(EditProfile::class)
            ->callAction('deleteMyAccount', data: ['password' => 'wrong-password'])
            ->assertHasActionErrors(['password']);

        $this->assertNotNull(User::find($student->id));
    }

    public function test_an_account_proposed_to_a_company_is_not_deleted(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('student'));

        foreach (['proposed', 'interview', 'coe'] as $status) {
            $student = $this->student();
            $this->application($student, $status);

            $this->actingAs($student);
            Livewire::test(EditProfile::class)
                ->callAction('deleteMyAccount', data: ['password' => 'password']);

            $this->assertNotNull(User::find($student->id), $status);
        }
    }

    public function test_the_profile_page_offers_deleting_the_account(): void
    {
        $this->actingAs($this->student())->get('/dashboard/profile')
            ->assertOk()
            ->assertSee(__('Delete my account'))
            ->assertDontSee('site-auth-bg', false);   // the sign-in artwork belongs to the sign-in pages only
    }
}
