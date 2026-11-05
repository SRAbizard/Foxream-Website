<?php

namespace App\Support;

/**
 * Phase 2 demo data for the homepage. Each translatable field is stored as
 * ['id' => ..., 'en' => ..., 'ja' => ...] — the exact same shape Spatie
 * Translatable will write to JSON columns in Phase 3, so migration is a
 * one-liner: replace `static::benefits()` with `Benefit::published()->get()`
 * and Spatie's `HasTranslations` trait will hydrate the same array shape.
 */
final class HomepageDemoData
{
    public static function pick(array $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return $field[$locale] ?? $field['id'] ?? reset($field) ?: '';
    }

    public static function benefits(): array
    {
        return [
            [
                'icon'  => 'M11.49 3.17c.39-.78 1.5-.78 1.9 0l1.84 3.67 4.06.59c.86.12 1.2 1.18.58 1.78l-2.94 2.86.69 4.03c.15.86-.75 1.51-1.51 1.1l-3.62-1.9-3.63 1.9c-.77.4-1.66-.25-1.51-1.1l.69-4.03L4.1 9.21c-.62-.6-.28-1.66.58-1.78l4.06-.59 1.84-3.67z',
                'title' => [
                    'id' => 'Proses Mudah dan Fleksibel',
                    'en' => 'Easy and Flexible Process',
                    'ja' => 'シンプルで柔軟なプロセス',
                ],
                'desc' => [
                    'id' => 'Administrasi online, sesi interview virtual, dan pertemuan langsung dengan perusahaan Jepang tanpa ribet.',
                    'en' => 'Online administration, virtual interview sessions, and direct meetings with Japanese companies — without the hassle.',
                    'ja' => 'オンライン手続き、バーチャル面接、日本企業との直接面談 — すべて簡単に。',
                ],
            ],
            [
                'icon'  => 'M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8v1m0 14v1m0-16a8 8 0 100 16 8 8 0 000-16z',
                'title' => [
                    'id' => 'Kualitas Terbaik dengan Harga Bersahabat',
                    'en' => 'Top Quality at a Friendly Price',
                    'ja' => '最高品質を手の届く価格で',
                ],
                'desc' => [
                    'id' => 'Biaya transparan dengan opsi pembiayaan fleksibel — mentor profesional yang menemani Anda hingga penempatan.',
                    'en' => 'Transparent pricing with flexible financing — professional mentors guide you all the way to placement.',
                    'ja' => '透明な料金体系と柔軟な支払いオプション — 配属までプロのメンターが伴走します。',
                ],
            ],
            [
                'icon'  => 'M9 13l-3-3m0 0l3-3m-3 3h12a4 4 0 014 4v2',
                'title' => [
                    'id' => 'Pilihan Karir yang Sesuai untuk Kamu',
                    'en' => 'Career Paths That Match You',
                    'ja' => 'あなたに合ったキャリアの選択肢',
                ],
                'desc' => [
                    'id' => 'Hospitality, Manufacturing, IT, Office, dan Senior Care — pilih jalur karier yang cocok dengan keahlianmu.',
                    'en' => 'Hospitality, Manufacturing, IT, Office, and Senior Care — pick the career path that fits your skills.',
                    'ja' => 'ホスピタリティ、製造、IT、オフィス、介護 — スキルに合ったキャリアパスを選択。',
                ],
            ],
        ];
    }

    public static function programs(): array
    {
        return [
            ['name' => 'Tokutei Ginou', 'tag' => ['id' => 'Visa', 'en' => 'Visa', 'ja' => 'ビザ'], 'desc' => [
                'id' => 'Specified Skilled Worker untuk 14 sektor industri Jepang.',
                'en' => 'Specified Skilled Worker visa for 14 Japanese industry sectors.',
                'ja' => '日本の14産業分野向け特定技能ビザ。',
            ]],
            ['name' => 'Engineering / Gijinkoku', 'tag' => ['id' => 'Visa', 'en' => 'Visa', 'ja' => 'ビザ'], 'desc' => [
                'id' => 'Visa engineer untuk lulusan teknik / IT.',
                'en' => 'Engineer visa for engineering / IT graduates.',
                'ja' => '工学・IT卒業者向けの技術ビザ。',
            ]],
            ['name' => 'Internship', 'tag' => ['id' => 'Magang', 'en' => 'Internship', 'ja' => '実習'], 'desc' => [
                'id' => 'Program magang teknis 1–3 tahun di perusahaan Jepang.',
                'en' => '1–3 year technical internship at Japanese companies.',
                'ja' => '日本企業での1〜3年の技能実習プログラム。',
            ]],
            ['name' => 'JLPT N5 & N4', 'tag' => ['id' => 'Bahasa', 'en' => 'Language', 'ja' => '語学'], 'desc' => [
                'id' => 'Kelas bahasa Jepang persiapan ujian JLPT.',
                'en' => 'Japanese language classes preparing for JLPT exam.',
                'ja' => 'JLPT試験対策の日本語クラス。',
            ]],
            ['name' => 'Driver Jepang', 'tag' => ['id' => 'Karier', 'en' => 'Career', 'ja' => 'キャリア'], 'desc' => [
                'id' => 'Jalur karier driver komersial di Jepang.',
                'en' => 'Career path for commercial drivers in Japan.',
                'ja' => '日本での商業ドライバーキャリアパス。',
            ]],
            ['name' => 'Kaigo (Caregiver)', 'tag' => ['id' => 'Karier', 'en' => 'Career', 'ja' => 'キャリア'], 'desc' => [
                'id' => 'Pelatihan dan penempatan tenaga perawat lansia.',
                'en' => 'Training and placement for elderly caregivers.',
                'ja' => '介護人材の研修と配属。',
            ]],
        ];
    }

    public static function jobs(): array
    {
        return [
            [
                'category' => 'Gijinkoku / Engineering',
                'items' => [
                    ['title' => ['id' => 'Civil Engineer', 'en' => 'Civil Engineer', 'ja' => '土木技師'], 'company' => 'PT Sakura Indah', 'location' => 'Tokyo, Japan', 'salary' => '¥220K–280K', 'visa' => 'Engineering', 'tag' => ['id' => 'Konstruksi', 'en' => 'Construction', 'ja' => '建設']],
                    ['title' => ['id' => 'IT Software Engineer', 'en' => 'IT Software Engineer', 'ja' => 'ITソフトウェアエンジニア'], 'company' => 'Nippon Tech Co.', 'location' => 'Osaka, Japan', 'salary' => '¥260K–350K', 'visa' => 'Engineering', 'tag' => ['id' => 'IT', 'en' => 'IT', 'ja' => 'IT']],
                    ['title' => ['id' => 'Office Administrator', 'en' => 'Office Administrator', 'ja' => 'オフィス管理者'], 'company' => 'Yamato Trading', 'location' => 'Yokohama, Japan', 'salary' => '¥190K–230K', 'visa' => 'Engineering', 'tag' => ['id' => 'Administrasi', 'en' => 'Administration', 'ja' => '事務']],
                ],
            ],
            [
                'category' => 'Tokutei Ginou',
                'items' => [
                    ['title' => ['id' => 'Caregiver (Kaigo)', 'en' => 'Caregiver (Kaigo)', 'ja' => '介護士'], 'company' => 'Sakura Care Home', 'location' => 'Nagoya, Japan', 'salary' => '¥180K–210K', 'visa' => 'Tokutei Ginou', 'tag' => ['id' => 'Kaigo', 'en' => 'Caregiving', 'ja' => '介護']],
                    ['title' => ['id' => 'Hotel Service Staff', 'en' => 'Hotel Service Staff', 'ja' => 'ホテルサービススタッフ'], 'company' => 'Hotel Kyoto Royal', 'location' => 'Kyoto, Japan', 'salary' => '¥170K–200K', 'visa' => 'Tokutei Ginou', 'tag' => ['id' => 'Perhotelan', 'en' => 'Hospitality', 'ja' => 'ホスピタリティ']],
                    ['title' => ['id' => 'Food Production', 'en' => 'Food Production', 'ja' => '食品製造'], 'company' => 'Asahi Foods', 'location' => 'Sapporo, Japan', 'salary' => '¥175K–195K', 'visa' => 'Tokutei Ginou', 'tag' => ['id' => 'Restoran', 'en' => 'Food Service', 'ja' => '飲食']],
                ],
            ],
        ];
    }

    public static function faqs(): array
    {
        return [
            ['q' => [
                'id' => 'Apa saja syarat untuk melamar kerja ke Jepang?',
                'en' => 'What do I need to apply for a job in Japan?',
                'ja' => '日本での仕事に応募するには何が必要ですか？',
            ], 'a' => [
                'id' => 'Umumnya paspor, ijazah terakhir, sertifikat bahasa Jepang (JLPT/JFT), dan sertifikat keahlian sesuai bidang. Syarat lengkap tercantum di setiap lowongan.',
                'en' => 'Usually a passport, your latest diploma, a Japanese language certificate (JLPT/JFT), and a skill certificate for the field. Full requirements are listed on each job posting.',
                'ja' => '一般的にパスポート、最終学歴の卒業証書、日本語能力証明（JLPT/JFT）、分野に応じた技能証明が必要です。詳しい条件は各求人に記載しています。',
            ]],
            ['q' => [
                'id' => 'Saya belum bisa bahasa Jepang, bagaimana?',
                'en' => "I don't speak Japanese yet — what should I do?",
                'ja' => 'まだ日本語が話せませんが、どうすればいいですか？',
            ], 'a' => [
                'id' => 'Anda bisa belajar dulu di '.\App\Support\Brand::learning().', lembaga pelatihan bahasa Jepang kami. Setelah siap, lamar lowongan yang sesuai di website ini.',
                'en' => 'You can start learning at '.\App\Support\Brand::learning().', our Japanese language training institute. Once you are ready, apply for a matching job on this website.',
                'ja' => 'まずは当社の日本語研修機関「'.\App\Support\Brand::learning().'」で学ぶことができます。準備ができたら、このサイトで条件に合う求人に応募してください。',
            ]],
            ['q' => [
                'id' => 'Bagaimana cara mengikuti ujian kompetensi?',
                'en' => 'How do I take the competency exam?',
                'ja' => '技能試験はどのように受験しますか？',
            ], 'a' => [
                'id' => 'Ujian kompetensi diadakan langsung oleh lembaga resmi Jepang. Kami membantu pendaftaran, pelatihan, dan simulasi ujian.',
                'en' => 'Competency exams are administered directly by official Japanese authorities. We assist with registration, training, and mock exams.',
                'ja' => '技能試験は日本の公式機関が直接実施します。登録、研修、模擬試験までサポートいたします。',
            ]],
            ['q' => [
                'id' => 'Bagaimana cara memantau status lamaran saya?',
                'en' => 'How can I track my application status?',
                'ja' => '応募状況はどのように確認できますか？',
            ], 'a' => [
                'id' => 'Masuk ke dashboard lalu buka menu Lamaran Saya. Di sana terlihat tahap lamaran Anda (dikirim, ditinjau, jadwal interview, hingga penawaran kerja), dan Anda akan mendapat notifikasi setiap kali statusnya berubah.',
                'en' => 'Log in to your dashboard and open My Applications. You can see which stage each application is at (submitted, under review, interview scheduled, through to a job offer), and you get a notification whenever the status changes.',
                'ja' => 'ダッシュボードにログインし「応募履歴」を開いてください。各応募の進捗（送信済み、審査中、面接予定、内定まで）を確認でき、ステータスが変わるたびに通知が届きます。',
            ]],
            ['q' => [
                'id' => 'Bagaimana cara mendaftar ke program ini?',
                'en' => 'How do I register for the program?',
                'ja' => 'プログラムへの申し込み方法は？',
            ], 'a' => [
                'id' => 'Klik tombol "Get Started" di atas, isi formulir registrasi, dan tim kami akan menghubungi Anda untuk konsultasi gratis.',
                'en' => 'Click the "Get Started" button above, fill out the registration form, and our team will contact you for a free consultation.',
                'ja' => '上記の「はじめる」ボタンから登録フォームをご記入ください。担当者より無料相談のご連絡を差し上げます。',
            ]],
            ['q' => [
                'id' => 'Apakah ada opsi pembiayaan?',
                'en' => 'Are there financing options?',
                'ja' => '支払いオプションはありますか？',
            ], 'a' => [
                'id' => 'Ya, kami menyediakan cicilan dan opsi pembiayaan dengan partner lembaga keuangan terpercaya.',
                'en' => 'Yes, we offer instalments and financing through trusted partner institutions.',
                'ja' => '信頼できる金融機関パートナーを通じた分割払いや融資のオプションをご用意しています。',
            ]],
        ];
    }

    public static function testimonials(): array
    {
        return [
            'company' => [
                [
                    'name'  => 'PT Sakura Indah',
                    'role'  => ['id' => 'Mitra Perusahaan', 'en' => 'Partner Company', 'ja' => 'パートナー企業'],
                    'quote' => [
                        'id' => 'Kandidat dari '.\App\Support\Brand::name().' datang dengan bahasa Jepang yang siap pakai dan etos kerja yang baik. Sangat terbantu.',
                        'en' => 'Candidates from '.\App\Support\Brand::name().' arrive with work-ready Japanese and excellent work ethic. Hugely helpful.',
                        'ja' => \App\Support\Brand::name().' からの候補者は実務レベルの日本語と素晴らしい労働倫理を備えて来てくれます。大変助かっています。',
                    ],
                ],
                [
                    'name'  => 'PT Nippon Jaya',
                    'role'  => ['id' => 'Mitra Perusahaan', 'en' => 'Partner Company', 'ja' => 'パートナー企業'],
                    'quote' => [
                        'id' => 'Proses rekrutmen yang transparan dan cepat. Tim '.\App\Support\Brand::name().' profesional dari awal sampai penempatan.',
                        'en' => 'Transparent and fast recruitment process. The '.\App\Support\Brand::name().' team is professional from start to placement.',
                        'ja' => '透明で迅速な採用プロセス。'.\App\Support\Brand::name().' のチームは最初から配属まで一貫してプロフェッショナルです。',
                    ],
                ],
            ],
            'student' => [
                [
                    'name'  => 'Akira Tanaka',
                    'role'  => ['id' => 'Alumni — Engineering, Tokyo', 'en' => 'Alumnus — Engineering, Tokyo', 'ja' => '卒業生 — 技術職、東京'],
                    'quote' => [
                        'id' => 'Berkat persiapan bahasa dan interview di '.\App\Support\Brand::name().', saya berhasil bekerja di perusahaan teknik di Tokyo.',
                        'en' => 'Thanks to '.\App\Support\Brand::name().'\'s language and interview prep, I landed a role at an engineering firm in Tokyo.',
                        'ja' => \App\Support\Brand::name().' の語学・面接対策のおかげで、東京のエンジニアリング企業に就職できました。',
                    ],
                ],
                [
                    'name'  => 'Rina Sato',
                    'role'  => ['id' => 'Alumni — Kaigo, Nagoya', 'en' => 'Alumna — Caregiving, Nagoya', 'ja' => '卒業生 — 介護、名古屋'],
                    'quote' => [
                        'id' => 'Programnya jelas, mentornya sabar. Sekarang saya sudah bekerja sebagai caregiver di Nagoya.',
                        'en' => 'The program is clear and the mentors are patient. I now work as a caregiver in Nagoya.',
                        'ja' => 'プログラムは明確でメンターも親切でした。今は名古屋で介護士として働いています。',
                    ],
                ],
            ],
        ];
    }

    public static function workflow(): array
    {
        $steps = [
            [1,  'Konsultasi Pekerjaan',                       'Job Consultation',                       '就職相談',
                 'Sesi tanya-jawab dengan konsultan karir untuk menentukan jalur yang cocok.',
                 'Q&A session with a career consultant to find the right path.',
                 'キャリアコンサルタントと相談し、最適なパスを決定します。'],
            [2,  'Memilih Lowongan Tersedia',                  'Pick from Available Openings',           '掲載求人を選ぶ',
                 'Pilih posisi dari katalog lowongan terverifikasi kami.',
                 'Choose a role from our verified job catalogue.',
                 '当社の確認済み求人カタログから職種を選択。'],
            [3,  'Pengumpulan Berkas',                         'Document Collection',                    '書類の収集',
                 'Upload dokumen — KTP, ijazah, CV — melalui dashboard siswa.',
                 'Upload documents — ID, diploma, CV — via the student dashboard.',
                 '生徒ダッシュボード経由で身分証、卒業証明書、履歴書をアップロード。'],
            [4,  'Seleksi Administrasi',                       'Administrative Screening',               '書類選考',
                 'Tim kami memverifikasi dokumen sebelum diteruskan ke perusahaan.',
                 'Our team verifies documents before forwarding them to the company.',
                 '社内チームが書類を確認した上で企業へ送付。'],
            [5,  'Interview Tahap Pertama',                    'First-Stage Interview',                  '一次面接',
                 'Wawancara awal dengan perwakilan perusahaan via online.',
                 'Initial online interview with the company representative.',
                 '企業担当者とオンラインで一次面接。'],
            [6,  'Latihan Interview & Pemantapan Kaiwa',       'Interview Practice & Conversation Drill','面接練習・会話強化',
                 'Sesi role-play bersama mentor untuk membangun kepercayaan diri.',
                 'Role-play sessions with mentors to build confidence.',
                 'メンターとのロールプレイで自信を構築。'],
            [7,  'Interview Tahap Kedua',                      'Second-Stage Interview',                 '二次面接',
                 'Final interview langsung dengan tim HR perusahaan Jepang.',
                 'Final interview directly with the Japanese company\'s HR team.',
                 '日本企業の人事チームと最終面接。'],
            [8,  'Pengurusan Administrasi Keberangkatan',      'Departure Administration',               '出国手続き',
                 'Visa, COE, tiket, dan asuransi diurus oleh tim kami.',
                 'Visa, COE, tickets, and insurance handled by our team.',
                 'ビザ、COE、航空券、保険を当社チームで手配。'],
            [9,  'Berangkat Ke Jepang',                         'Departure to Japan',                    '日本へ出発',
                 'Penerbangan ke Jepang dengan pendampingan dokumen lengkap.',
                 'Flight to Japan with complete document support.',
                 '必要書類完備のもと日本へフライト。'],
            [10, 'Tiba di Jepang dan Penjemputan',             'Arrival and Pickup in Japan',            '日本到着・送迎',
                 'Pickup di bandara, orientasi tempat tinggal dan pengenalan perusahaan.',
                 'Airport pickup, housing orientation, and company onboarding.',
                 '空港送迎、住居案内、企業オリエンテーション。'],
        ];

        return array_map(fn ($s) => [
            'step' => $s[0],
            'title' => ['id' => $s[1], 'en' => $s[2], 'ja' => $s[3]],
            'desc'  => ['id' => $s[4], 'en' => $s[5], 'ja' => $s[6]],
        ], $steps);
    }

    /**
     * Per-visa workflow slides for the homepage Process section.
     *
     * Reads from the visa_workflow_steps table (admin-editable). Falls
     * back to the hardcoded defaults when the DB is empty or the table
     * doesn't exist yet (e.g. during initial install).
     */
    public static function visaWorkflows(): array
    {
        try {
            $visas = \App\Models\VisaCategory::query()
                ->orderBy('sort_order')
                ->with(['workflowSteps' => fn ($q) => $q->orderBy('sort_order')])
                ->whereHas('workflowSteps')
                ->get();

            if ($visas->isNotEmpty()) {
                return $visas->map(function ($v) {
                    return [
                        'slug'    => $v->slug,
                        'name'    => $v->getTranslations('name'),
                        'tagline' => $v->getTranslations('description') ?: ['id' => '', 'en' => '', 'ja' => ''],
                        'steps'   => $v->workflowSteps->map(function ($s, $i) {
                            $step = [
                                'n'        => $i + 1,
                                'title'    => $s->getTranslations('title'),
                                'icon'     => $s->icon ?: null,
                                'icon_url' => $s->icon_url,
                            ];
                            if ($s->badge_color && $s->badge_label) {
                                $step['badge'] = [
                                    'color' => $s->badge_color,
                                    'label' => $s->getTranslations('badge_label'),
                                ];
                            }
                            return $step;
                        })->all(),
                        'notes'   => [],
                    ];
                })->all();
            }
        } catch (\Throwable) {
            // Table missing during fresh install — fall through to defaults
        }

        return static::visaWorkflowsDefaults();
    }

    /**
     * Hardcoded fallback used when the DB is empty.
     */
    private static function visaWorkflowsDefaults(): array
    {
        // Common building blocks reused across visas
        $preScreen = ['Pre-Screening Document', 'Pre-Screening Document', '事前書類審査'];
        $explainJob = ['Penjelasan Job dan Biaya', 'Job & Cost Briefing', '仕事と費用の説明'];
        $interviewTSK = ['Interview dengan TSK', 'Interview with TSK', 'TSK面接'];
        $training = ['Pelatihan Pra Interview dan Bahasa', 'Pre-Interview & Language Training', '面接前トレーニング'];
        $interviewCo = ['Interview dengan Perusahaan', 'Interview with Company', '企業面接'];
        $contract = ['Kontrak Kerja', 'Work Contract', '雇用契約'];
        $medical = ['Medical Check Up', 'Medical Check-up', '健康診断'];
        $sending = ['Sending Document', 'Document Sending', '書類送付'];
        $passport = ['Pembuatan Passport', 'Passport Application', 'パスポート申請'];
        $coe = ['CoE Terbit', 'CoE Issued', '在留資格認定証明書発行'];
        $visa = ['Visa Terbit', 'Visa Issued', 'ビザ発行'];
        $departPrep = ['Persiapan Berangkat ke Jepang', 'Pre-Departure Preparation', '出発準備'];
        $pickup = ['Penjemputan di Jepang', 'Pickup in Japan', '日本到着・送迎'];
        $work = ['Kerja di Jepang', 'Working in Japan', '日本で就労'];

        $title = fn (array $t) => ['id' => $t[0], 'en' => $t[1], 'ja' => $t[2]];

        return [
            // ─── SSW / Tokutei Ginou ───────────────────────────────
            [
                'slug'    => 'tokutei-ginou',
                'name'    => ['id' => 'SSW · Tokutei Ginou', 'en' => 'SSW · Tokutei Ginou', 'ja' => 'SSW・特定技能'],
                'tagline' => [
                    'id' => 'Alur lengkap untuk visa pekerja terampil khusus.',
                    'en' => 'Complete pipeline for the Specified Skilled Worker visa.',
                    'ja' => '特定技能ビザの完全なフロー。',
                ],
                'steps' => [
                    ['n' => 1,  'title' => $title($preScreen)],
                    ['n' => 2,  'title' => $title($explainJob)],
                    ['n' => 3,  'title' => $title($interviewTSK), 'badge' => ['label' => ['id' => 'Deposit Rp 2 Juta', 'en' => 'IDR 2M Deposit', 'ja' => '保証金200万Rp'], 'color' => 'warning']],
                    ['n' => 4,  'title' => $title($training)],
                    ['n' => 5,  'title' => $title($interviewCo)],
                    ['n' => 6,  'title' => $title($medical)],
                    ['n' => 7,  'title' => $title($sending)],
                    ['n' => 8,  'title' => $title($contract), 'badge' => ['label' => ['id' => 'Pembiayaan Tahap 1 (50%)', 'en' => 'Stage 1 Payment (50%)', 'ja' => '第1期支払い (50%)'], 'color' => 'brand']],
                    ['n' => 9,  'title' => $title($passport)],
                    ['n' => 10, 'title' => $title($coe), 'badge' => ['label' => ['id' => 'Pembiayaan Tahap 2 (50%)', 'en' => 'Stage 2 Payment (50%)', 'ja' => '第2期支払い (50%)'], 'color' => 'brand']],
                    ['n' => 11, 'title' => $title($visa)],
                    ['n' => 12, 'title' => $title($departPrep)],
                    ['n' => 13, 'title' => $title($pickup)],
                ],
                'notes' => [
                    [
                        'id' => 'Passport dan Medical Check Up ditanggung masing-masing kandidat.',
                        'en' => 'Passport and Medical Check-up are paid by each candidate.',
                        'ja' => 'パスポートと健康診断は各候補者の自己負担です。',
                    ],
                    [
                        'id' => 'Biaya tiket pesawat bisa dipinjamkan oleh perusahaan penerima.',
                        'en' => 'Flight ticket costs may be advanced by the receiving company.',
                        'ja' => '航空券費用は受入企業から立替可能です。',
                    ],
                ],
            ],

            // ─── Engineer / Gijinkoku ──────────────────────────────
            [
                'slug'    => 'engineering',
                'name'    => ['id' => 'Engineer · Gijinkoku', 'en' => 'Engineer · Gijinkoku', 'ja' => 'エンジニア・技人国'],
                'tagline' => [
                    'id' => 'Untuk lulusan D3/S1 yang menargetkan posisi profesional di Jepang.',
                    'en' => 'For diploma/bachelor graduates targeting professional roles in Japan.',
                    'ja' => '日本での専門職を目指すD3/S1卒業者向け。',
                ],
                'steps' => [
                    ['n' => 1,  'title' => $title($preScreen), 'badge' => ['label' => ['id' => 'D3/S1 · JLPT N3+', 'en' => 'D3/S1 · JLPT N3+', 'ja' => 'D3/S1・JLPT N3以上'], 'color' => 'info']],
                    ['n' => 2,  'title' => $title($explainJob)],
                    ['n' => 3,  'title' => $title($interviewTSK)],
                    ['n' => 4,  'title' => $title($training)],
                    ['n' => 5,  'title' => $title($interviewCo)],
                    ['n' => 6,  'title' => $title($contract)],
                    ['n' => 7,  'title' => $title($medical)],
                    ['n' => 8,  'title' => $title($sending)],
                    ['n' => 9,  'title' => $title($passport)],
                    ['n' => 10, 'title' => $title($coe)],
                    ['n' => 11, 'title' => $title($visa)],
                    ['n' => 12, 'title' => $title($departPrep)],
                    ['n' => 13, 'title' => $title($pickup)],
                    ['n' => 14, 'title' => $title($work)],
                ],
                'notes' => [],
            ],

            // ─── Internship ────────────────────────────────────────
            [
                'slug'    => 'internship',
                'name'    => ['id' => 'Internship', 'en' => 'Internship', 'ja' => 'インターンシップ'],
                'tagline' => [
                    'id' => 'Visa magang untuk mahasiswa aktif semester 3 ke atas.',
                    'en' => 'Internship visa for active university students from semester 3 onward.',
                    'ja' => '大学3年以上の現役学生向けインターンシップビザ。',
                ],
                'steps' => [
                    ['n' => 1,  'title' => ['id' => 'MOU dengan Universitas', 'en' => 'University MOU', 'ja' => '大学とのMOU']],
                    ['n' => 2,  'title' => $title($explainJob)],
                    ['n' => 3,  'title' => $title($preScreen), 'badge' => ['label' => ['id' => 'Mahasiswa Aktif Sem. 3+', 'en' => 'Active Students Sem 3+', 'ja' => '3年以上の現役学生'], 'color' => 'info']],
                    ['n' => 4,  'title' => $title($interviewTSK)],
                    ['n' => 5,  'title' => $title($training)],
                    ['n' => 6,  'title' => $title($interviewCo)],
                    ['n' => 7,  'title' => $title($contract)],
                    ['n' => 8,  'title' => $title($medical)],
                    ['n' => 9,  'title' => $title($sending)],
                    ['n' => 10, 'title' => $title($passport)],
                    ['n' => 11, 'title' => $title($coe)],
                    ['n' => 12, 'title' => $title($visa)],
                    ['n' => 13, 'title' => $title($departPrep)],
                    ['n' => 14, 'title' => $title($pickup)],
                    ['n' => 15, 'title' => $title($work), 'badge' => ['label' => ['id' => 'Gaji Bersih Rp 8-10 Juta', 'en' => 'Net IDR 8-10M Salary', 'ja' => '手取り月8-10百万Rp'], 'color' => 'success']],
                ],
                'notes' => [],
            ],
        ];
    }
}
