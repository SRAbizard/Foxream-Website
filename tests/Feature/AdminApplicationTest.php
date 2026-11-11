<?php

namespace Tests\Feature;

use App\Filament\Resources\Applications\Pages\EditApplication;
use App\Models\Application;
use App\Notifications\ApplicationStatusChanged;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminApplicationTest extends TestCase
{
    private function application(): Application
    {
        return Application::query()->forceCreate([
            'user_id'        => $this->student(ready: true)->id,
            'job_vacancy_id' => $this->vacancy()->id,
            'status'         => 'submitted',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_moves_an_application_to_the_next_step_and_the_candidate_is_told(): void
    {
        Notification::fake();
        $application = $this->application();
        $admin = $this->admin();

        $this->actingAs($admin);
        Livewire::test(EditApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('nextStage');

        $application->refresh();
        $this->assertSame('screening_id', $application->status);
        $this->assertSame($admin->id, $application->reviewed_by);
        Notification::assertSentTo($application->user, ApplicationStatusChanged::class);
    }

    public function test_admin_can_jump_to_any_step(): void
    {
        $application = $this->application();

        $this->actingAs($this->admin());
        Livewire::test(EditApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('changeStage', data: ['status' => 'interview']);

        $this->assertSame('interview', $application->fresh()->status);
    }

    public function test_the_steps_run_in_order_to_departure(): void
    {
        $application = $this->application();
        $seen = [$application->status];

        while ($next = $application->nextStage()) {
            $application->update(['status' => $next]);
            $seen[] = $next;
        }

        $this->assertSame(Application::STAGES, $seen);
        $this->assertSame('departed', $application->status);
    }

    public function test_admin_pages_open(): void
    {
        $application = $this->application();
        $this->actingAs($this->admin());

        foreach (['/admin', '/admin/applications', '/admin/students', '/admin/job-vacancies', '/admin/companies',
                  '/admin/site-settings', '/admin/legal-settings', "/admin/applications/{$application->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
