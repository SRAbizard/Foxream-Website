<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The fields on offer are now the twelve SSW (Tokutei Ginou) fields only.
 * Vacancies in a retired category move to the closest new one; those with
 * no SSW equivalent (engineer, logistics, restaurant) are left without a
 * category rather than filed under the wrong one.
 */
return new class extends Migration
{
    private const FIELDS = [
        ['driver-truck',       'SSW Driver Truck',          'SSW Truck Driver',           '自動車運送業（トラック）', 'heroicon-o-truck'],
        ['driver-taksi',       'SSW Driver Taksi',          'SSW Taxi Driver',            '自動車運送業（タクシー）', 'heroicon-o-map-pin'],
        ['driver-bus',         'SSW Driver Bus',            'SSW Bus Driver',             '自動車運送業（バス）',     'heroicon-o-user-group'],
        ['kaigo',              'SSW Perawat Lansia',        'SSW Caregiver',              '介護',                     'heroicon-o-heart'],
        ['konstruksi',         'SSW Konstruksi',            'SSW Construction',           '建設',                     'heroicon-o-building-office-2'],
        ['manufaktur',         'SSW Manufaktur',            'SSW Manufacturing',          '工業製品製造業',           'heroicon-o-cog-6-tooth'],
        ['pengolahan-makanan', 'SSW Pengolahan Makanan',    'SSW Food Processing',        '飲食料品製造業',           'heroicon-o-cake'],
        ['pertanian',          'SSW Pertanian',             'SSW Agriculture',            '農業（耕種農業）',         'heroicon-o-sun'],
        ['peternakan',         'SSW Peternakan',            'SSW Livestock',              '農業（畜産農業）',         'heroicon-o-globe-asia-australia'],
        ['building-cleaning',  'SSW Building Cleaning',     'SSW Building Cleaning',      'ビルクリーニング',         'heroicon-o-sparkles'],
        ['perhotelan',         'SSW Perhotelan',            'SSW Hospitality',            '宿泊',                     'heroicon-o-home-modern'],
        ['otomotif',           'SSW Pemeliharaan Otomotif', 'SSW Automotive Maintenance', '自動車整備',               'heroicon-o-wrench-screwdriver'],
    ];

    /** Retired slug => the new field its vacancies move to. */
    private const MOVES = [
        'driver'      => 'driver-truck',
        'pabrik'      => 'manufaktur',
        'kikai-kakou' => 'manufaktur',
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::FIELDS as $i => [$slug, $id, $en, $ja, $icon]) {
            $row = [
                'name'       => json_encode(['id' => $id, 'en' => $en, 'ja' => $ja], JSON_UNESCAPED_UNICODE),
                'icon'       => $icon,
                'sort_order' => $i + 1,
                'updated_at' => $now,
            ];
            DB::table('job_categories')->where('slug', $slug)->exists()
                ? DB::table('job_categories')->where('slug', $slug)->update($row)
                : DB::table('job_categories')->insert($row + ['slug' => $slug, 'color' => '#b32510', 'created_at' => $now]);
        }

        $ids = DB::table('job_categories')->pluck('id', 'slug');
        $keep = array_column(self::FIELDS, 0);

        foreach ($ids as $slug => $id) {
            if (in_array($slug, $keep, true)) {
                continue;
            }
            DB::table('job_vacancies')->where('job_category_id', $id)
                ->update(['job_category_id' => isset(self::MOVES[$slug]) ? $ids[self::MOVES[$slug]] : null]);
            DB::table('job_categories')->where('id', $id)->delete();
        }
    }

    public function down(): void
    {
        // The retired categories are not restored.
    }
};
