<?php

namespace Database\Seeders;

use App\Models\VisaCategory;
use App\Models\VisaWorkflowStep;
use Illuminate\Database\Seeder;

class VisaWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Reusable building blocks: title trio + heroicon per step archetype.
        $blocks = [
            'preScreen'    => [['Pre-Screening Document', 'Pre-Screening Document', '事前書類審査'],            'heroicon-o-document-magnifying-glass'],
            'mouUni'       => [['MOU dengan Universitas', 'University MOU', '大学とのMOU'],                       'heroicon-o-document-text'],
            'explainJob'   => [['Penjelasan Pekerjaan', 'Job Briefing', '仕事の説明'],                'heroicon-o-chat-bubble-left-right'],
            'interviewTSK' => [['Interview dengan TSK', 'Interview with TSK', 'TSK面接'],                         'heroicon-o-user-group'],
            'training'     => [['Pelatihan Pra Interview dan Bahasa', 'Pre-Interview & Language Training', '面接前トレーニング'], 'heroicon-o-academic-cap'],
            'interviewCo'  => [['Interview dengan Perusahaan', 'Interview with Company', '企業面接'],            'heroicon-o-briefcase'],
            'contract'     => [['Kontrak Kerja', 'Work Contract', '雇用契約'],                                    'heroicon-o-document-check'],
            'medical'      => [['Medical Check Up', 'Medical Check-up', '健康診断'],                              'heroicon-o-heart'],
            'sending'      => [['Sending Document', 'Document Sending', '書類送付'],                              'heroicon-o-paper-airplane'],
            'passport'     => [['Pembuatan Passport', 'Passport Application', 'パスポート申請'],                  'heroicon-o-globe-alt'],
            'coe'          => [['CoE Terbit', 'CoE Issued', '在留資格認定証明書発行'],                            'heroicon-o-clipboard-document-check'],
            'visa'         => [['Visa Terbit', 'Visa Issued', 'ビザ発行'],                                        'heroicon-o-identification'],
            'departPrep'   => [['Persiapan Berangkat ke Jepang', 'Pre-Departure Preparation', '出発準備'],         'heroicon-o-shopping-bag'],
            'pickup'       => [['Penjemputan di Jepang', 'Pickup in Japan', '日本到着・送迎'],                    'heroicon-o-map-pin'],
            'work'         => [['Kerja di Jepang', 'Working in Japan', '日本で就労'],                             'heroicon-o-building-office-2'],
        ];

        $title = fn (string $key) => ['id' => $blocks[$key][0][0], 'en' => $blocks[$key][0][1], 'ja' => $blocks[$key][0][2]];
        $icon  = fn (string $key) => $blocks[$key][1];

        // ─── Tokutei Ginou (SSW) — 13 steps ─────────────────────────
        $this->seed('tokutei-ginou', [
            ['preScreen'],
            ['explainJob'],
            ['interviewTSK'],
            ['training'],
            ['interviewCo'],
            ['medical'],
            ['sending'],
            ['contract'],
            ['passport'],
            ['coe'],
            ['visa'],
            ['departPrep'],
            ['pickup'],
        ], $title, $icon);

        // ─── Engineering / Gijinkoku — 14 steps ─────────────────────
        $this->seed('engineering', [
            ['preScreen', ['info', ['id' => 'D3/S1 · JLPT N3+', 'en' => 'D3/S1 · JLPT N3+', 'ja' => 'D3/S1・JLPT N3以上']]],
            ['explainJob'],
            ['interviewTSK'],
            ['training'],
            ['interviewCo'],
            ['contract'],
            ['medical'],
            ['sending'],
            ['passport'],
            ['coe'],
            ['visa'],
            ['departPrep'],
            ['pickup'],
            ['work'],
        ], $title, $icon);

        // ─── Internship — 15 steps ──────────────────────────────────
        $this->seed('internship', [
            ['mouUni'],
            ['explainJob'],
            ['preScreen', ['info', ['id' => 'Mahasiswa Aktif Sem. 3+', 'en' => 'Active Students Sem 3+', 'ja' => '3年以上の現役学生']]],
            ['interviewTSK'],
            ['training'],
            ['interviewCo'],
            ['contract'],
            ['medical'],
            ['sending'],
            ['passport'],
            ['coe'],
            ['visa'],
            ['departPrep'],
            ['pickup'],
            ['work'],
        ], $title, $icon);
    }

    /**
     * Seed steps for one visa, in order. Each item is [blockKey] or
     * [blockKey, [color, label]] when a badge is wanted.
     *
     * Idempotent — only seeds if the visa has zero steps yet, so admin
     * customisation is preserved on subsequent runs.
     */
    private function seed(string $visaSlug, array $steps, callable $title, callable $icon): void
    {
        $visa = VisaCategory::where('slug', $visaSlug)->first();
        if (! $visa) return;

        // Don't overwrite admin-customised workflows
        if ($visa->workflowSteps()->exists()) return;

        foreach ($steps as $i => $step) {
            $key   = $step[0];
            $badge = $step[1] ?? null;

            VisaWorkflowStep::create([
                'visa_category_id' => $visa->id,
                'sort_order'       => $i + 1,
                'title'            => $title($key),
                'icon'             => $icon($key),
                'badge_color'      => $badge[0] ?? null,
                'badge_label'      => $badge[1] ?? null,
            ]);
        }
    }
}
