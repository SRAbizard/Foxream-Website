<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Tests\TestCase;

/** Who may open what, and what the live site never allows. */
class AccessTest extends TestCase
{
    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/dashboard')->assertRedirect('/dashboard/login');
    }

    public function test_a_candidate_cannot_open_the_admin_panel(): void
    {
        $this->actingAs($this->student())->get('/admin')->assertForbidden();
    }

    public function test_a_candidate_opens_their_dashboard(): void
    {
        $this->actingAs($this->student())->get('/dashboard')->assertOk();
    }

    public function test_an_admin_opens_the_admin_panel(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_an_admin_without_manage_students_sees_only_the_website_side(): void
    {
        $this->actingAs($this->admin(managesStudents: false));

        $this->get('/admin/job-vacancies')->assertOk();
        $this->get('/admin/applications')->assertForbidden();
        $this->get('/admin/students')->assertForbidden();
    }

    public function test_a_deactivated_account_is_shut_out(): void
    {
        $student = $this->student();
        $student->forceFill(['is_active' => false])->save();

        $this->actingAs($student->fresh())->get('/dashboard')->assertForbidden();
    }

    public function test_one_click_demo_sign_in_does_not_exist_outside_a_laptop(): void
    {
        $this->get('/dev/login-as/1')->assertNotFound();
    }

    public function test_a_candidate_cannot_download_someone_elses_cv(): void
    {
        $other = $this->student();

        $this->actingAs($this->student())->get("/admin/students/{$other->id}/rirekisho.pdf")->assertForbidden();
    }

    public function test_the_live_site_refuses_a_weak_first_admin(): void
    {
        $this->app['env'] = 'production';
        config(['site.bootstrap_admin' => ['name' => 'Owner', 'email' => 'admin@example.com', 'password' => 'password']]);

        $this->expectException(\RuntimeException::class);
        app(RolesAndPermissionsSeeder::class)->run();
    }

    public function test_the_live_site_accepts_a_strong_first_admin(): void
    {
        $this->app['env'] = 'production';
        config(['site.bootstrap_admin' => ['name' => 'Owner', 'email' => 'owner@company.test', 'password' => 'A-long-and-strong-pass-42']]);

        app(RolesAndPermissionsSeeder::class)->run();

        $this->assertTrue(User::where('email', 'owner@company.test')->firstOrFail()->hasRole('superadmin'));
    }
}
