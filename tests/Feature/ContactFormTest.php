<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    private function message(array $override = []): array
    {
        return [
            'name'         => 'Budi Santoso',
            'email'        => 'budi@example.com',
            'subject'      => 'Pertanyaan program',
            'message'      => 'Saya ingin bertanya tentang program SSW.',
            'form_started' => time() - 30,
            ...$override,
        ];
    }

    public function test_a_candidate_message_is_saved(): void
    {
        $this->from('/contact')->post('/contact', $this->message())->assertRedirect();

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_a_company_request_is_saved(): void
    {
        $this->from('/contact?for=employer')->post('/contact/employer', [
            'company' => 'Kabushiki Test', 'name' => 'Tanaka', 'email' => 'tanaka@example.jp',
            'workers' => 5, 'form_started' => time() - 30,
        ])->assertRedirect();

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_missing_fields_are_refused(): void
    {
        $this->from('/contact')->post('/contact', $this->message(['email' => 'not-an-email', 'message' => 'short']))
            ->assertSessionHasErrors(['email', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_bots_get_thanks_but_nothing_is_saved(): void
    {
        // The hidden field filled in, or the form sent within 3 seconds.
        $this->from('/contact')->post('/contact', $this->message(['website' => 'http://spam.test']))->assertRedirect();
        $this->from('/contact')->post('/contact', $this->message(['form_started' => time()]))->assertRedirect();

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_more_than_five_messages_a_minute_are_refused(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/contact')->post('/contact', $this->message())->assertRedirect();
        }

        $this->from('/contact')->post('/contact', $this->message())->assertStatus(429);
    }
}
