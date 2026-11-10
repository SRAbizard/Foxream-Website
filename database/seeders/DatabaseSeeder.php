<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * What a new install needs: roles and the first admin, empty site settings,
 * document types, job and visa categories, visa steps and the legal pages'
 * defaults. No sample content: for that, on a laptop only,
 *
 *   php artisan db:seed --class=SampleContentSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CmsSeeder::class,
            DocumentTypeSeeder::class,
            RecruitmentSeeder::class,
            VisaWorkflowSeeder::class,
        ]);
    }
}
