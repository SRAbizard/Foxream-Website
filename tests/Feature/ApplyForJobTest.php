<?php

namespace Tests\Feature;

use App\Models\Application;
use Tests\TestCase;

class ApplyForJobTest extends TestCase
{
    public function test_a_ready_candidate_applies_once(): void
    {
        $student = $this->student(ready: true);
        $job     = $this->vacancy();

        $this->actingAs($student)->post("/jobs/{$job->slug}/apply")->assertRedirect("/jobs/{$job->slug}");
        $this->actingAs($student)->post("/jobs/{$job->slug}/apply")->assertRedirect("/jobs/{$job->slug}");

        $this->assertSame(1, Application::count());
        $this->assertSame('submitted', Application::first()->status);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $job = $this->vacancy();

        $this->post("/jobs/{$job->slug}/apply")->assertRedirect(route('filament.student.auth.login'));
        $this->assertSame(0, Application::count());
    }

    public function test_an_admin_cannot_apply(): void
    {
        $job = $this->vacancy();

        $this->actingAs($this->admin())->post("/jobs/{$job->slug}/apply");
        $this->assertSame(0, Application::count());
    }

    public function test_a_candidate_without_an_approved_visa_target_cannot_apply(): void
    {
        $job = $this->vacancy();

        $this->actingAs($this->student())->post("/jobs/{$job->slug}/apply")
            ->assertSessionHas('status', __('Your visa target must be approved by our team before you can apply.'));
        $this->assertSame(0, Application::count());
    }

    public function test_a_candidate_cannot_apply_for_another_visa(): void
    {
        $student = $this->student(ready: true, visa: 'tokutei-ginou');
        $job     = $this->vacancy('engineering');

        $this->actingAs($student)->post("/jobs/{$job->slug}/apply");
        $this->assertSame(0, Application::count());
    }

    public function test_a_candidate_with_an_unfinished_cv_cannot_apply(): void
    {
        $student = $this->student(ready: true);
        $student->profile()->update(['self_pr_ja' => null]);
        $job = $this->vacancy();

        $this->actingAs($student)->post("/jobs/{$job->slug}/apply");
        $this->assertSame(0, Application::count());
    }

    public function test_an_expired_job_takes_no_applications(): void
    {
        $student = $this->student(ready: true);
        $job     = $this->vacancy(attributes: ['expires_at' => now()->subDay()]);

        $this->actingAs($student)->post("/jobs/{$job->slug}/apply")->assertNotFound();
        $this->assertSame(0, Application::count());
    }
}
