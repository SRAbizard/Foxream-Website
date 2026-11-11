<?php

namespace Tests\Feature;

use App\Mail\AlertMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['ops.alert_email' => 'ops@example.com', 'ops.errors.enabled' => true]);
        Route::middleware('web')->post('/_test/broken', fn () => throw new RuntimeException('Something broke'));
    }

    public function test_an_error_on_a_page_is_emailed_without_what_was_typed(): void
    {
        $this->post('/_test/broken', ['passport_number' => 'X1234567'])->assertStatus(500);

        Mail::assertSent(AlertMail::class, function (AlertMail $mail) {
            return $mail->hasTo('ops@example.com')
                && str_contains($mail->title, 'Something broke')
                && str_contains($mail->lines['Page'], '/_test/broken')
                && ! str_contains(json_encode($mail->lines), 'X1234567');
        });
    }

    public function test_the_same_error_is_emailed_once_an_hour(): void
    {
        $this->post('/_test/broken')->assertStatus(500);
        $this->post('/_test/broken')->assertStatus(500);

        Mail::assertSentCount(1);
    }

    public function test_no_emails_when_switched_off_or_no_address(): void
    {
        config(['ops.errors.enabled' => false]);
        $this->post('/_test/broken')->assertStatus(500);

        config(['ops.errors.enabled' => true, 'ops.alert_email' => null]);
        $this->post('/_test/broken')->assertStatus(500);

        Mail::assertNothingSent();
    }

    public function test_a_missing_page_is_not_an_error(): void
    {
        $this->get('/no-such-page')->assertNotFound();

        Mail::assertNothingSent();
    }

    public function test_daily_limit(): void
    {
        config(['ops.errors.per_day' => 2]);

        // Three different errors (each made on its own line).
        report(new RuntimeException('First'));
        report(new RuntimeException('Second'));
        report(new RuntimeException('Third'));

        Mail::assertSentCount(2);
    }
}
