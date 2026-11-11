<?php

namespace Tests;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\User;
use App\Models\VisaCategory;
use App\Support\DemoAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

/**
 * Every test starts from a new install: an empty in-memory database with
 * what `php artisan db:seed` gives the live site (roles, the first admin,
 * job and visa categories, visa steps) and no sample content.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** An admin; `managesStudents` = given "manage students" (applications, students, documents). */
    protected function admin(bool $managesStudents = true): User
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        if ($managesStudents) {
            $admin->givePermissionTo('manage students');
        }

        return $admin;
    }

    /** A candidate; `ready` = visa target approved and a complete CV, so they may apply. */
    protected function student(bool $ready = false, string $visa = 'tokutei-ginou'): User
    {
        $student = User::factory()->create();

        if ($ready) {
            Storage::fake('public');
            DemoAccounts::fillCv($student);
            $student->profile()->update([
                'primary_visa_category_id' => VisaCategory::where('slug', $visa)->value('id'),
                'visa_target_status'       => 'confirmed',
            ]);
        }

        return $student->fresh();
    }

    protected function vacancy(string $visa = 'tokutei-ginou', array $attributes = []): JobVacancy
    {
        $company = Company::query()->forceCreate([
            'slug' => 'test-company-'.uniqid(), 'name' => 'Test Company', 'country' => 'Japan',
            'city' => 'Osaka', 'is_verified' => true, 'is_active' => true,
        ]);

        return JobVacancy::query()->forceCreate([
            'slug'              => 'test-job-'.uniqid(),
            'company_id'        => $company->id,
            'visa_category_id'  => VisaCategory::where('slug', $visa)->value('id'),
            'title'             => ['id' => 'Pengemudi Truk', 'en' => 'Truck Driver', 'ja' => 'トラック運転手'],
            'description'       => ['id' => 'Mengemudikan truk antarkota.', 'en' => 'Driving trucks between cities.', 'ja' => '都市間のトラック運転。'],
            'location_city'     => 'Osaka',
            'salary_min'        => 220000,
            'salary_currency'   => 'JPY',
            'salary_period'     => 'monthly',
            'positions'         => 3,
            'published_at'      => now()->subDay(),
            ...$attributes,
        ]);
    }
}
