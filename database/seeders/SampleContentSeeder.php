<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sample content for trying the site on a laptop: FAQs, testimonials,
 * companies, vacancies, a blog post, an event and a gallery album. Never
 * run it on the live site; DatabaseSeeder leaves it out.
 *
 *   php artisan db:seed --class=SampleContentSeeder
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        app(CmsSeeder::class)->seedSamples();
        app(RecruitmentSeeder::class)->seedSamples();
        $this->call(LaunchContentSeeder::class);
    }
}
