<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobCategory;
use App\Models\JobVacancy;
use App\Models\VisaCategory;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedJobCategories();
        $this->seedVisaCategories();
    }

    /** Sample companies and vacancies (SampleContentSeeder). */
    public function seedSamples(): void
    {
        $this->seedCompanies();
        $this->seedVacancies();
    }

    private function seedJobCategories(): void
    {
        // The twelve SSW (Tokutei Ginou) fields on offer; Japanese names are
        // the official field names and go into the CV's 応募職種.
        $cats = [
            ['driver-truck',       ['id'=>'SSW Driver Truck',          'en'=>'SSW Truck Driver',           'ja'=>'自動車運送業（トラック）'], 'heroicon-o-truck'],
            ['driver-taksi',       ['id'=>'SSW Driver Taksi',          'en'=>'SSW Taxi Driver',            'ja'=>'自動車運送業（タクシー）'], 'heroicon-o-map-pin'],
            ['driver-bus',         ['id'=>'SSW Driver Bus',            'en'=>'SSW Bus Driver',             'ja'=>'自動車運送業（バス）'],     'heroicon-o-user-group'],
            ['kaigo',              ['id'=>'SSW Perawat Lansia',        'en'=>'SSW Caregiver',              'ja'=>'介護'],                     'heroicon-o-heart'],
            ['konstruksi',         ['id'=>'SSW Konstruksi',            'en'=>'SSW Construction',           'ja'=>'建設'],                     'heroicon-o-building-office-2'],
            ['manufaktur',         ['id'=>'SSW Manufaktur',            'en'=>'SSW Manufacturing',          'ja'=>'工業製品製造業'],           'heroicon-o-cog-6-tooth'],
            ['pengolahan-makanan', ['id'=>'SSW Pengolahan Makanan',    'en'=>'SSW Food Processing',        'ja'=>'飲食料品製造業'],           'heroicon-o-cake'],
            ['pertanian',          ['id'=>'SSW Pertanian',             'en'=>'SSW Agriculture',            'ja'=>'農業（耕種農業）'],         'heroicon-o-sun'],
            ['peternakan',         ['id'=>'SSW Peternakan',            'en'=>'SSW Livestock',              'ja'=>'農業（畜産農業）'],         'heroicon-o-globe-asia-australia'],
            ['building-cleaning',  ['id'=>'SSW Building Cleaning',     'en'=>'SSW Building Cleaning',      'ja'=>'ビルクリーニング'],         'heroicon-o-sparkles'],
            ['perhotelan',         ['id'=>'SSW Perhotelan',            'en'=>'SSW Hospitality',            'ja'=>'宿泊'],                     'heroicon-o-home-modern'],
            ['otomotif',           ['id'=>'SSW Pemeliharaan Otomotif', 'en'=>'SSW Automotive Maintenance', 'ja'=>'自動車整備'],               'heroicon-o-wrench-screwdriver'],
        ];
        foreach ($cats as $i => [$slug, $name, $icon]) {
            JobCategory::firstOrCreate(['slug' => $slug], ['name' => $name, 'icon' => $icon, 'sort_order' => $i + 1]);
        }
    }

    private function seedVisaCategories(): void
    {
        $visas = [
            ['tokutei-ginou', ['id'=>'Tokutei Ginou', 'en'=>'Specified Skilled Worker', 'ja'=>'特定技能'],
                ['id'=>'Visa kerja khusus untuk 14 sektor industri Jepang.', 'en'=>'Work visa for 14 specified industry sectors.', 'ja'=>'14産業分野の特定技能ビザ。'],
                // required: counts toward progress %
                ['ktp','kk','passport','ijazah','transcript','photo','medical_check','sktm_polri','jlpt_certificate','skill_certificate'],
                // optional: accepted but not blocking
                ['cv']],
            ['engineering',   ['id'=>'Engineering / Gijinkoku', 'en'=>'Engineer / Specialist in Humanities', 'ja'=>'技術・人文知識・国際業務'],
                ['id'=>'Visa untuk profesional teknik, IT, dan posisi spesialis.', 'en'=>'Visa for engineering, IT, and specialist professionals.', 'ja'=>'エンジニア、IT、専門職向けビザ。'],
                ['ktp','passport','ijazah','transcript','cv','photo','jlpt_certificate'],
                ['kk','skill_certificate']],
            ['internship',    ['id'=>'Internship', 'en'=>'Internship', 'ja'=>'インターンシップ'],
                [
                    'id' => 'Visa untuk mahasiswa aktif semester 3 ke atas, dengan maksimal kerja di Jepang 1 tahun.',
                    'en' => 'Visa for active university students from semester 3 onward, with up to 1 year of work in Japan.',
                    'ja' => '大学3年以上の現役学生向けビザ。日本での就労は最大1年。',
                ],
                ['ktp','kk','passport','ijazah','transcript','photo','medical_check','sktm','sktm_polri'],
                ['cv','jlpt_certificate']],
        ];
        // firstOrCreate so admin-edited names / descriptions / required docs
        // are kept on re-seed. Only missing visa slugs get the defaults.
        foreach ($visas as $i => [$slug, $name, $desc, $reqDocs, $optDocs]) {
            VisaCategory::firstOrCreate(['slug' => $slug], [
                'name'               => $name,
                'description'        => $desc,
                'sort_order'         => $i + 1,
                'required_documents' => $reqDocs,
                'optional_documents' => $optDocs,
            ]);
        }
    }

    private function seedCompanies(): void
    {
        $companies = [
            ['pt-sakura-indah', 'PT Sakura Indah', 'Construction', 'Japan',    'Tokyo',    true],
            ['nippon-tech',     'Nippon Tech Co.', 'IT',           'Japan',    'Osaka',    true],
            ['hotel-kyoto',     'Hotel Kyoto Royal','Hospitality', 'Japan',    'Kyoto',    true],
            ['sakura-care',     'Sakura Care Home','Caregiving',   'Japan',    'Nagoya',   true],
            ['asahi-foods',     'Asahi Foods',     'Food',         'Japan',    'Sapporo',  true],
            ['yamato-trading',  'Yamato Trading',  'Trading',      'Japan',    'Yokohama', true],
        ];
        foreach ($companies as [$slug, $name, $industry, $country, $city, $verified]) {
            Company::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'industry' => $industry, 'country' => $country,
                'city' => $city, 'is_verified' => $verified, 'is_active' => true,
            ]);
        }
    }

    private function seedVacancies(): void
    {
        $byCompany = Company::pluck('id', 'slug');
        $byJobCat  = JobCategory::pluck('id', 'slug');
        $byVisa    = VisaCategory::pluck('id', 'slug');

        $vacancies = [
            [
                'slug' => 'civil-engineer-pt-sakura-indah',
                'company' => 'pt-sakura-indah', 'job' => null, 'visa' => 'engineering',
                'title' => ['id'=>'Civil Engineer','en'=>'Civil Engineer','ja'=>'土木エンジニア'],
                'desc'  => ['id'=>'<p>Bertanggung jawab atas perencanaan dan pengawasan proyek konstruksi sipil di Tokyo.</p>','en'=>'<p>Plan and supervise civil construction projects in Tokyo.</p>','ja'=>'<p>東京での土木建設プロジェクトの計画と監督。</p>'],
                'req'   => ['id'=>'<ul><li>Lulusan teknik sipil</li><li>JLPT N3 minimum</li></ul>','en'=>'<ul><li>Civil engineering degree</li><li>JLPT N3 minimum</li></ul>','ja'=>'<ul><li>土木工学学位</li><li>JLPT N3 以上</li></ul>'],
                'city' => 'Tokyo', 'prefecture' => 'Tokyo',
                'min' => 220000, 'max' => 280000, 'featured' => true,
            ],
            [
                'slug' => 'it-software-engineer-nippon-tech',
                'company' => 'nippon-tech', 'job' => null, 'visa' => 'engineering',
                'title' => ['id'=>'IT Software Engineer','en'=>'IT Software Engineer','ja'=>'IT ソフトウェアエンジニア'],
                'desc'  => ['id'=>'<p>Bangun aplikasi web modern dengan stack Laravel + Vue.</p>','en'=>'<p>Build modern web applications with Laravel + Vue stack.</p>','ja'=>'<p>Laravel と Vue で最新のウェブアプリを開発。</p>'],
                'city' => 'Osaka', 'prefecture' => 'Osaka',
                'min' => 260000, 'max' => 350000, 'featured' => true,
            ],
            [
                'slug' => 'caregiver-sakura-care',
                'company' => 'sakura-care', 'job' => 'kaigo', 'visa' => 'tokutei-ginou',
                'title' => ['id'=>'Caregiver (Kaigo)','en'=>'Caregiver (Kaigo)','ja'=>'介護士'],
                'desc'  => ['id'=>'<p>Membantu kebutuhan harian lansia di panti perawatan Sakura Care.</p>','en'=>'<p>Support daily activities of elderly residents at Sakura Care home.</p>','ja'=>'<p>サクラケアホームでのご高齢者の日常生活サポート。</p>'],
                'city' => 'Nagoya', 'prefecture' => 'Aichi',
                'min' => 180000, 'max' => 210000, 'featured' => true,
            ],
            [
                'slug' => 'hotel-service-staff-kyoto-royal',
                'company' => 'hotel-kyoto', 'job' => 'perhotelan', 'visa' => 'tokutei-ginou',
                'title' => ['id'=>'Hotel Service Staff','en'=>'Hotel Service Staff','ja'=>'ホテルサービススタッフ'],
                'desc'  => ['id'=>'<p>Layanan tamu front desk dan housekeeping di hotel butik Kyoto.</p>','en'=>'<p>Front-desk guest service and housekeeping at a boutique Kyoto hotel.</p>','ja'=>'<p>京都のブティックホテルでのフロント業務と客室管理。</p>'],
                'city' => 'Kyoto', 'prefecture' => 'Kyoto',
                'min' => 170000, 'max' => 200000,
            ],
            [
                'slug' => 'food-production-asahi',
                'company' => 'asahi-foods', 'job' => 'pengolahan-makanan', 'visa' => 'tokutei-ginou',
                'title' => ['id'=>'Food Production','en'=>'Food Production','ja'=>'食品製造'],
                'desc'  => ['id'=>'<p>Operator lini produksi makanan di pabrik Asahi Foods Sapporo.</p>','en'=>'<p>Food production line operator at Asahi Foods Sapporo plant.</p>','ja'=>'<p>札幌アサヒフーズ工場での食品製造ライン作業。</p>'],
                'city' => 'Sapporo', 'prefecture' => 'Hokkaido',
                'min' => 175000, 'max' => 195000,
            ],
            [
                'slug' => 'office-administrator-yamato',
                'company' => 'yamato-trading', 'job' => null, 'visa' => 'engineering',
                'title' => ['id'=>'Office Administrator','en'=>'Office Administrator','ja'=>'オフィス管理者'],
                'desc'  => ['id'=>'<p>Mendukung operasional kantor perdagangan Yamato di Yokohama.</p>','en'=>'<p>Support office operations at Yamato Trading in Yokohama.</p>','ja'=>'<p>横浜のヤマト商事でのオフィス運営をサポート。</p>'],
                'city' => 'Yokohama', 'prefecture' => 'Kanagawa',
                'min' => 190000, 'max' => 230000,
            ],
        ];

        foreach ($vacancies as $i => $v) {
            JobVacancy::firstOrCreate(['slug' => $v['slug']], [
                'company_id'         => $byCompany[$v['company']] ?? null,
                'job_category_id'    => $byJobCat[$v['job']] ?? null,
                'visa_category_id'   => $byVisa[$v['visa']] ?? null,
                'title'              => $v['title'],
                'description'        => $v['desc'],
                'requirements'       => $v['req'] ?? null,
                'benefits'           => null,
                'location_city'      => $v['city'],
                'location_prefecture'=> $v['prefecture'],
                'salary_min'         => $v['min'],
                'salary_max'         => $v['max'],
                'salary_currency'    => 'JPY',
                'salary_period'      => 'monthly',
                'employment_type'    => 'fulltime',
                'positions'          => rand(1, 3),
                'published_at'       => now()->subDays($i + 1),
                'is_featured'        => $v['featured'] ?? false,
            ]);
        }
    }
}
