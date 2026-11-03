<?php

namespace App\Support;

use App\Models\JobVacancy;

/**
 * The structured Job Order attached to a vacancy (job_vacancies.job_order).
 * One definition drives both the admin form and the generated Job Order
 * PDF, so the two never drift apart. It holds only what a candidate needs
 * to decide whether to apply: TSK and company paperwork (representative,
 * capital, payroll closing dates…) stays out.
 *
 * Each field: [label (Indonesian, the document language), Japanese term,
 * input type, example placeholder].
 */
class JobOrder
{
    public const INSURANCE = [
        'kenko'  => 'Asuransi Kesehatan (Kenkou Hoken)',
        'kosei'  => 'Pensiun Kesejahteraan (Kousei Nenkin)',
        'koyo'   => 'Asuransi Ketenagakerjaan (Koyou Hoken)',
        'rosai'  => 'Asuransi Kecelakaan Kerja (Rousai Hoken)',
    ];

    public const EMPLOYMENT = [
        'seishain' => 'Karyawan Tetap (Seishain)',
        'keiyaku'  => 'Karyawan Kontrak (Keiyaku-shain)',
        'arubaito' => 'Paruh Waktu (Arubaito)',
        'haken'    => 'Karyawan Alih Daya (Haken)',
    ];

    /** @return array<string, array{title: string, jp: string, fields: array<string, array{0: string, 1: string, 2: string, 3?: string}>}> */
    public static function sections(): array
    {
        return [
            'company' => ['title' => 'Tentang Perusahaan', 'jp' => '企業情報', 'fields' => [
                'name'          => ['Nama Perusahaan', '企業名', 'text', 'Fujisouko Co., Ltd. — atau tulis "Dirahasiakan"'],
                'business'      => ['Bidang Usaha', '事業内容', 'textarea', 'Pengiriman rute tetap untuk minimarket Lawson dan pergudangan'],
                'employees'     => ['Jumlah Karyawan', '従業員数', 'text', '50 orang'],
                'foreign_staff' => ['Karyawan Asing Saat Ini', '外国人社員', 'text', 'Ada, dari Myanmar (bagian gudang)'],
                'about'         => ['Keunggulan Tempat Kerja', '職場環境', 'textarea', "- Bersertifikat tempat kerja ramah karyawan\n- Aktif mendukung karyawan asing"],
            ]],
            'job' => ['title' => 'Pekerjaan', 'jp' => '仕事内容', 'fields' => [
                'job_type'      => ['Isi Pekerjaan', '仕事内容', 'textarea', 'Memasak, melayani tamu, dan membantu pengelolaan toko'],
                'work_location' => ['Lokasi Kerja', '勤務地', 'textarea', "- Kota Onomichi, Prefektur Hiroshima\n- Kota Mihara, Prefektur Hiroshima"],
                'employment'    => ['Status Kepegawaian', '雇用形態', 'select'],
                'contract'      => ['Masa Kontrak', '契約期間', 'text', '1 tahun, dapat diperpanjang'],
                'probation'     => ['Masa Percobaan', '試用期間', 'text', '3 bulan (gaji tidak berubah)'],
            ]],
            'pay' => ['title' => 'Gaji & Tunjangan', 'jp' => '給与・手当', 'fields' => [
                'basic_salary' => ['Gaji Pokok', '基本給', 'text', '¥180.000 / bulan'],
                'allowances'   => ['Tunjangan', '諸手当', 'textarea', "- Shift malam: ¥6.000 / shift\n- Transportasi: sesuai biaya, maks. ¥50.000 / bulan"],
                'overtime_pay' => ['Upah Lembur', '割増賃金', 'textarea', 'Dibayar terpisah: ¥1.722 / jam (+25%)'],
                'deductions'   => ['Potongan', '控除', 'textarea', "- Pajak: ± ¥5.000\n- Asuransi sosial: ± ¥37.000\n- Sewa asrama: ¥25.000"],
                'take_home'    => ['Perkiraan Gaji Diterima', '手取り目安', 'text', '± ¥180.000 / bulan (setelah potongan)'],
                'bonus'        => ['Bonus', '賞与', 'text', '1 kali setahun, ¥50.000 – ¥100.000'],
                'raise'        => ['Kenaikan Gaji', '昇給', 'text', '1 kali setahun (± ¥10.000)'],
            ]],
            'hours' => ['title' => 'Jam Kerja & Libur', 'jp' => '勤務時間・休日', 'fields' => [
                'work_hours'      => ['Jam Kerja', '勤務時間', 'textarea', "- 08.30 – 17.30 (istirahat 60 menit)\n- Sistem shift: ada"],
                'overtime'        => ['Lembur', '残業', 'text', 'Rata-rata 20 jam / bulan'],
                'holidays'        => ['Hari Libur', '休日', 'textarea', 'Sabtu, Minggu, dan libur akhir tahun'],
                'annual_holidays' => ['Libur per Tahun', '年間休日', 'text', '108 hari'],
                'paid_leave'      => ['Cuti Berbayar', '有給休暇', 'text', '10 hari setelah 6 bulan bekerja'],
            ]],
            'welfare' => ['title' => 'Tempat Tinggal & Fasilitas', 'jp' => '住居・福利厚生', 'fields' => [
                'accommodation' => ['Tempat Tinggal', '住居', 'textarea', 'Asrama perusahaan, ¥25.000 / bulan (belum termasuk listrik & air)'],
                'meals'         => ['Makan', '食事', 'text', 'Makan siang disediakan'],
                'insurance'     => ['Asuransi', '社会保険', 'checklist'],
                'facilities'    => ['Fasilitas Lain', 'その他', 'textarea', 'Penjemputan di bandara, bantuan pembukaan rekening bank'],
            ]],
            'qualification' => ['title' => 'Syarat Kandidat', 'jp' => '応募条件', 'fields' => [
                'gender'    => ['Jenis Kelamin', '性別', 'text', 'Laki-laki & perempuan'],
                'age'       => ['Usia', '年齢', 'text', '20 – 35 tahun'],
                'language'  => ['Bahasa Jepang', '日本語能力', 'text', 'JLPT N4 / JFT-Basic A2 (N3 lebih diutamakan)'],
                'licenses'  => ['Sertifikat Wajib', '必要資格', 'textarea', "- Lulus ujian keterampilan Tokutei Ginou bidang terkait\n- SIM (untuk pengemudi)"],
                'education' => ['Pendidikan & Pengalaman', '学歴・経験', 'textarea', 'Minimal lulusan SMA/SMK; pengalaman 1 tahun lebih diutamakan'],
                'others'    => ['Syarat Lain', 'その他条件', 'textarea', 'Sehat jasmani dan rohani, tidak buta warna'],
            ]],
            'other' => ['title' => 'Seleksi', 'jp' => '選考', 'fields' => [
                'accepted'  => ['Jumlah yang Diterima', '採用人数', 'text', '2 orang'],
                'interview' => ['Wawancara', '面接', 'text', 'Online (Zoom), 2 kali'],
                'schedule'  => ['Jadwal', '日程', 'textarea', "- Wawancara: November 2026\n- Mulai kerja: April 2027"],
                'notes'     => ['Catatan', '備考', 'textarea'],
            ]],
        ];
    }

    /** True when at least one Job Order field has been filled in. */
    public static function filled(JobVacancy $vacancy): bool
    {
        return collect(self::sections())
            ->flatMap(fn (array $s, string $key) => collect($s['fields'])->keys()->map(fn ($f) => data_get($vacancy->job_order, "{$key}.{$f}")))
            ->filter(fn ($v) => filled($v))
            ->isNotEmpty();
    }

    /**
     * The sections ready for the PDF: only filled rows, with select and
     * checklist values turned into text. Company name falls back to the
     * vacancy's company.
     *
     * @return array<int, array{title: string, jp: string, rows: array<int, array{label: string, jp: string, value: string|array}>}>
     */
    public static function forDocument(JobVacancy $vacancy): array
    {
        $data = $vacancy->job_order ?? [];
        data_fill($data, 'company.name', $vacancy->company?->name);

        $out = [];
        foreach (self::sections() as $key => $section) {
            $rows = [];
            foreach ($section['fields'] as $field => [$label, $jp, $type]) {
                $value = $data[$key][$field] ?? null;
                $value = match ($type) {
                    'select'    => self::EMPLOYMENT[$value] ?? $value,
                    'checklist' => collect((array) $value)->map(fn ($k) => self::INSURANCE[$k] ?? $k)->values()->all(),
                    default     => is_string($value) ? trim($value) : $value,
                };
                if ($type === 'checklist' && filled($data[$key]['insurance_note'] ?? null)) {
                    $value[] = trim($data[$key]['insurance_note']);
                }
                if (filled($value)) {
                    $rows[] = ['label' => $label, 'jp' => $jp, 'value' => $value];
                }
            }
            if ($rows) {
                $out[] = ['title' => $section['title'], 'jp' => $section['jp'], 'rows' => $rows];
            }
        }

        return $out;
    }

    /**
     * The four numbers a candidate looks for first, shown as boxes at the
     * top of the PDF. Salary falls back to the vacancy's salary range.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function highlights(JobVacancy $vacancy): array
    {
        $jo = $vacancy->job_order ?? [];
        $salary = data_get($jo, 'pay.basic_salary') ?: self::salaryRange($vacancy);

        return collect([
            ['label' => 'Gaji Pokok', 'value' => $salary],
            ['label' => 'Perkiraan Diterima', 'value' => data_get($jo, 'pay.take_home')],
            ['label' => 'Bahasa Jepang', 'value' => data_get($jo, 'qualification.language')],
            ['label' => 'Libur per Tahun', 'value' => data_get($jo, 'hours.annual_holidays')],
        ])
            // Boxes are small: keep the figure, drop the explanation in brackets.
            ->map(fn ($h) => ['label' => $h['label'], 'value' => trim(preg_replace('/\s*\(.*$/us', '', (string) $h['value']))])
            ->filter(fn ($h) => $h['value'] !== '')->values()->all();
    }

    /** "¥180.000 – ¥193.000 / bulan" from the vacancy's salary fields. */
    public static function salaryRange(JobVacancy $vacancy): ?string
    {
        if (! $vacancy->salary_min && ! $vacancy->salary_max) {
            return null;
        }
        $symbol = ['JPY' => '¥', 'IDR' => 'Rp', 'USD' => '$'][$vacancy->salary_currency] ?? '';
        $amount = fn ($n) => $symbol.number_format((float) $n, 0, ',', '.');
        $range  = collect([$vacancy->salary_min, $vacancy->salary_max])->filter()->unique()->map($amount)->join(' – ');
        $per    = ['monthly' => 'bulan', 'yearly' => 'tahun', 'hourly' => 'jam'][$vacancy->salary_period] ?? null;

        return $range.($per ? " / {$per}" : '');
    }

    /** The company name a candidate sees: the Job Order's (may be "Dirahasiakan"), else the company's. */
    public static function companyName(JobVacancy $vacancy): ?string
    {
        return data_get($vacancy->job_order, 'company.name') ?: $vacancy->company?->name;
    }

    /** "Job Order – Truck Driver – Fujisouko Co., Ltd. [TG - Driver]" */
    public static function documentTitle(JobVacancy $vacancy): string
    {
        $parts = array_filter([$vacancy->t('title', 'id'), self::companyName($vacancy)]);
        $code  = trim((string) $vacancy->job_order_code);

        return 'Job Order – '.implode(' – ', $parts).($code !== '' ? " [{$code}]" : '');
    }

    /**
     * A textarea value as lines in their order: lines starting with "-",
     * "•" or "*" are bullet points.
     *
     * @return array<int, array{bullet: bool, text: string}>
     */
    public static function lines(string $value): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $value) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $bullet = (bool) preg_match('/^[-•*・]\s*(.+)$/u', $line, $m);
            $out[] = ['bullet' => $bullet, 'text' => $bullet ? $m[1] : $line];
        }

        return $out;
    }

    /**
     * A highlight value split for its box: the figure ("¥178.000 – ¥193.000")
     * and its period after the last " / " ("bulan"), with the range kept on
     * one line. Other slashes ("JLPT N4 / JFT-Basic A2") stay in the figure.
     *
     * @return array{main: string, unit: ?string}
     */
    public static function figure(string $value): array
    {
        $unit = null;
        if (preg_match('#^(.+?)\s*/\s*((?:per\s+)?(?:bulan|tahun|jam|hari|minggu|shift|month|year|hour|day|week))$#iu', $value, $m)) {
            [$value, $unit] = [$m[1], $m[2]];
        }

        return ['main' => preg_replace('/\s+([–-])\s+/u', "\u{00A0}$1\u{00A0}", trim($value)), 'unit' => $unit];
    }
}
