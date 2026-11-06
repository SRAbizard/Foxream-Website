<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\HomepageDemoData;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedFaqs();
        $this->seedTestimonials();
        // Blog posts and events: see LaunchContentSeeder.
    }

    private function seedSettings(): void
    {
        $defaults = [
            'contact.email'    => 'info@example.com',
            'contact.phone'    => null,
            'contact.whatsapp' => null,
            'social.instagram' => null,
            'social.facebook'  => null,
            'social.tiktok'    => null,
            'stats.students'   => '0',
            'stats.workers'    => '0',
            'stats.companies'  => '0',
        ];
        foreach ($defaults as $k => $v) {
            $group = explode('.', $k)[0];
            Setting::set($k, $v, $group);
        }
    }

    private function seedFaqs(): void
    {
        foreach (HomepageDemoData::faqs() as $i => $f) {
            Faq::firstOrCreate(
                ['question->id' => $f['q']['id']],
                [
                    'question'     => $f['q'],
                    'answer'       => $f['a'],
                    'sort_order'   => $i + 1,
                    'is_published' => true,
                ],
            );
        }
    }

    private function seedTestimonials(): void
    {
        foreach (HomepageDemoData::testimonials()['company'] as $i => $t) {
            Testimonial::firstOrCreate(
                ['name' => $t['name'], 'kind' => 'company'],
                ['role' => $t['role'], 'quote' => $t['quote'], 'kind' => 'company', 'sort_order' => $i + 1, 'is_published' => true],
            );
        }
        foreach (HomepageDemoData::testimonials()['student'] as $i => $t) {
            Testimonial::firstOrCreate(
                ['name' => $t['name'], 'kind' => 'student'],
                ['role' => $t['role'], 'quote' => $t['quote'], 'kind' => 'student', 'sort_order' => $i + 1, 'is_published' => true],
            );
        }
    }
}
