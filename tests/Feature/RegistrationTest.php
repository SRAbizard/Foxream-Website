<?php

namespace Tests\Feature;

use App\Filament\Auth\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('student'));
    }

    private function form(array $override = []): array
    {
        return [
            'name'                 => 'Siti Rahma',
            'email'                => 'siti@example.com',
            'password'             => 'Kandidat-2026!',
            'passwordConfirmation' => 'Kandidat-2026!',
            'consent'              => true,
            ...$override,
        ];
    }

    public function test_a_candidate_signs_up_and_must_confirm_the_email(): void
    {
        Notification::fake();

        Livewire::test(Register::class)->fillForm($this->form())->call('register')->assertHasNoFormErrors();

        $user = User::where('email', 'siti@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('student'));
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signing_up_needs_the_terms_ticked(): void
    {
        Livewire::test(Register::class)->fillForm($this->form(['consent' => false]))->call('register')
            ->assertHasFormErrors(['consent' => 'accepted']);

        $this->assertDatabaseMissing('users', ['email' => 'siti@example.com']);
    }

    public function test_the_same_email_cannot_sign_up_twice(): void
    {
        User::factory()->create(['email' => 'siti@example.com']);

        Livewire::test(Register::class)->fillForm($this->form())->call('register')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_an_unconfirmed_candidate_cannot_open_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect();
    }
}
