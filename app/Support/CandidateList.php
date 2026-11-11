<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The candidate list we send to a company (候補者リスト), in Japanese:
 * one sheet per job, one column per candidate. What the rows are depends
 * on the job's visa, as in our templates:
 *   - Tokutei Ginou: gender, Japanese level, our assessment, health, the driving
 *     licence for driver jobs and the SSW certificate
 *   - Internship:    gender, age, university and major
 *   - Gijinkoku:     Japanese level, our assessment, education, major,
 *     graduation and work history
 * Every list has the photo, the name and a link to the CV.
 */
class CandidateList
{
    /** Assessment scores (applications.assessment) => the list's row label. */
    public const SCORES = [
        'overall'   => '総合評価（5段階）',
        'character' => '人間性',
        'growth'    => '成長・定着',
        'skill'     => '専門知識・スキル',
        'japanese'  => '日本語会話力',
    ];

    /** The same scores in the admin form. */
    public const SCORE_LABELS = [
        'overall'   => 'Overall score',
        'character' => 'Character',
        'growth'    => 'Growth & staying power',
        'skill'     => 'Knowledge & skills',
        'japanese'  => 'Japanese conversation',
    ];

    /** Steps whose applications go on the list unless the admin picks others. */
    public const DEFAULT_STATUSES = ['submitted', 'screening_id', 'screening_jp', 'proposed', 'interview', 'passed', 'contract', 'coe', 'visa', 'departure'];

    private const HEADER_BLUE = '4A86E8';
    private const SCORE_YELLOW = 'FFF2CC';

    /** 信仰心の強さ: assessment value => the list's word (also a dropdown in the file). */
    private const DEVOTION_ROW = '信仰心の強さ';
    private const DEVOTION = ['strong' => '強い', 'normal' => '普通', 'light' => '弱い'];
    private const MARITAL = ['single' => '未婚', 'married' => '既婚', 'divorced' => '離婚', 'widowed' => '死別'];
    private const GENDER = ['male' => '男性', 'female' => '女性'];

    /** Education level => how the list writes it after the school (最終学歴). */
    private const LEVELS_JA = [
        'sma' => '高校', 'smk' => '専門高校',
        'd1' => '専門学校', 'd2' => '専門学校', 'd3' => '短期大学', 'd4' => '大学（応用）',
        's1' => '大学', 's2' => '大学院（修士）', 's3' => '大学院（博士）',
    ];
    private const LICENSES = ['sim_b1' => '中型車免許（B1免許）', 'sim_a' => '普通車免許（A免許）'];

    /** 1, 1.5 … 5 for the score dropdowns. */
    public static function scoreOptions(): array
    {
        return collect(self::scores())->mapWithKeys(fn ($n) => [(string) $n => (string) $n])->all();
    }

    /** Religious devotion for the admin's dropdown, with the list's word. */
    public static function devotionOptions(): array
    {
        $labels = ['strong' => __('Strong'), 'normal' => __('Normal'), 'light' => __('Light')];

        return collect(self::DEVOTION)->mapWithKeys(fn ($ja, $key) => [$key => $labels[$key].' ('.$ja.')'])->all();
    }

    /** @return array<int, float|int> */
    private static function scores(): array
    {
        return array_map(fn ($n) => $n % 2 ? $n / 2 : intdiv($n, 2), range(2, 10));
    }

    /** @param  array<int, string>  $statuses */
    public static function download(Company $company, array $statuses): BinaryFileResponse
    {
        $applications = Application::query()
            ->with('user.studentProfile.internshipCampus', 'user.educations', 'user.workExperiences', 'vacancy.jobCategory', 'vacancy.visaCategory')
            ->whereIn('status', $statuses)
            ->whereHas('vacancy', fn ($q) => $q->where('company_id', $company->id))
            ->get();

        $title = ($company->name_ja ?: $company->name).'の候補者リスト（LIST KANDIDAT '.Str::upper($company->name).'）';
        $book = new Spreadsheet();
        $book->getProperties()->setTitle($title)->setCreator(\App\Support\Brand::company());
        $book->removeSheetByIndex(0);

        foreach ($applications->groupBy('job_vacancy_id') as $group) {
            self::sheet($book, $group);
        }
        if ($book->getSheetCount() === 0) {
            $book->createSheet()->setTitle('候補者なし')->setCellValue('A1', '該当する候補者はいません。');
        }
        $book->setActiveSheetIndex(0);

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'candidates-'.bin2hex(random_bytes(6)).'.xlsx';
        (new Xlsx($book))->save($path);

        return response()->download($path, $title.'.xlsx')->deleteFileAfterSend();
    }

    /** One job's candidates, recommended and best scored first. */
    private static function sheet(Spreadsheet $book, Collection $applications): void
    {
        $vacancy = $applications->first()->vacancy;
        $applications = $applications->sortBy([
            fn ($a, $b) => (int) ! empty($b->assessment['recommended']) <=> (int) ! empty($a->assessment['recommended']),
            fn ($a, $b) => (float) ($b->assessment['overall'] ?? 0) <=> (float) ($a->assessment['overall'] ?? 0),
            fn ($a, $b) => $a->created_at <=> $b->created_at,
        ])->values();

        $sheet = $book->createSheet();
        $name = self::sheetTitle($vacancy?->t('title', 'ja') ?: $vacancy?->t('title') ?: '候補者');
        for ($n = 2; $book->sheetNameExists($name); $n++) {   // two jobs with the same title
            $name = Str::limit($name, 25, '').' ('.$n.')';
        }
        $sheet->setTitle($name);

        $visa = $vacancy?->visaCategory?->slug;
        $driver = Str::startsWith((string) $vacancy?->jobCategory?->slug, 'driver');
        $rows = match ($visa) {
            'internship'  => self::internshipRows(),
            'engineering' => self::gijinkokuRows(),
            default       => self::tokuteiGinouRows($driver),
        };

        foreach (array_keys($rows) as $r => $label) {
            $sheet->setCellValue([1, $r + 1], $label === 'kana' ? '' : $label);
        }

        foreach ($applications as $i => $application) {
            $col = $i + 2;
            foreach (array_values($rows) as $r => $value) {
                $value = $value($application, $i);
                if (is_array($value)) {       // [text, link]
                    $sheet->setCellValue([$col, $r + 1], $value[0]);
                    $sheet->getCell([$col, $r + 1])->getHyperlink()->setUrl($value[1]);
                    $sheet->getStyle([$col, $r + 1])->getFont()->setUnderline(true)->getColor()->setRGB('1155CC');
                } elseif (is_int($value) || is_float($value)) {
                    $sheet->setCellValue([$col, $r + 1], $value);
                } else {
                    $sheet->setCellValueExplicit([$col, $r + 1], (string) $value, DataType::TYPE_STRING);
                }
            }
            self::photo($sheet, $application, $col);
        }

        // The Internship template keeps the number row plain.
        self::style($sheet, array_keys($rows), max(1, $applications->count()), numberInBlue: $visa !== 'internship');
        self::dropdowns($sheet, array_keys($rows), max(1, $applications->count()));
    }

    /** The scores (1 … 5), 信仰心の強さ, 性別 and 来日歴 are picked from a list in each cell. */
    private static function dropdowns(Worksheet $sheet, array $labels, int $candidates): void
    {
        $last = Coordinate::stringFromColumnIndex($candidates + 1);
        $lists = [self::DEVOTION_ROW => array_values(self::DEVOTION), '性別' => array_values(self::GENDER), '来日歴' => ['有', '無']]
            + array_fill_keys(array_values(self::SCORES), self::scores());

        foreach ($labels as $r => $label) {
            if (! isset($lists[$label])) {
                continue;
            }
            $validation = (new DataValidation())
                ->setType(DataValidation::TYPE_LIST)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setFormula1('"'.implode(',', $lists[$label]).'"');
            $sheet->setDataValidation('B'.($r + 1).':'.$last.($r + 1), $validation);
        }
    }

    /**
     * Row label => how to fill it for one candidate. 'kana' is the
     * unlabelled row under the name.
     *
     * @return array<string, callable(Application, int): (string|int|float|array{0: string, 1: string})>
     */
    private static function tokuteiGinouRows(bool $driver): array
    {
        $p = self::profile(...);
        $yesNo = self::yesNo(...);

        $rows = self::head() + [
            '性別'     => self::gender(...),
            '年齢'     => self::age(...),
            '日本語検定' => fn (Application $a) => (string) Certificates::japaneseLevel($p($a)?->licenses),
        ] + self::scoreRows() + [
            '来日歴'     => self::beenToJapan(...),
            // Answered by the student on the profile; blank until they do.
            'タトゥー'   => fn (Application $a) => $yesNo($p($a)?->has_tattoo),
            '借金'       => fn (Application $a) => $yesNo($p($a)?->has_debt),
            '宗教'       => self::religion(...),
            self::DEVOTION_ROW => fn (Application $a) => self::DEVOTION[$a->assessment['devotion'] ?? ''] ?? '',
            'BMI'        => fn (Application $a) => self::bmi($p($a)?->height_cm, $p($a)?->weight_kg),
            '色盲検査'   => fn (Application $a) => $yesNo($p($a)?->colour_blind),
            '既往症'     => fn (Application $a) => filled($p($a)?->medical_conditions) ? (string) $p($a)->medical_conditions : '無',
            // As on the CV, whose 健康状態 is always 良好.
            '体力'       => fn () => '〇',
        ];
        if ($driver) {
            $rows['インドネシア運転免許'] = fn (Application $a) => self::license($p($a)?->licenses)[0];
            $rows['中型・大型受験資格'] = fn (Application $a) => self::license($p($a)?->licenses)[1];
        }

        return $rows + [
            '特定技能評価試験' => fn (Application $a) => self::ssw($p($a)?->licenses),
            '家族'           => self::family(...),
        ] + self::cvRow();
    }

    /** As in the Internship template: who they are and where they study. */
    private static function internshipRows(): array
    {
        return self::head() + [
            '性別'   => self::gender(...),
            '年齢'   => self::age(...),
            '来日歴' => self::beenToJapan(...),
            '大学'   => fn (Application $a) => Str::upper((string) (self::profile($a)?->campusName() ?: self::school($a)?->institution)),
            '専攻'   => fn (Application $a) => self::major(self::school($a)),
        ] + self::cvRow();
    }

    /** Gijinkoku: the Japanese level and our assessment, then the degree and the work behind it. */
    private static function gijinkokuRows(): array
    {
        return self::head() + [
            '性別'     => self::gender(...),
            '年齢'     => self::age(...),
            '日本語検定' => fn (Application $a) => (string) Certificates::japaneseLevel(self::profile($a)?->licenses),
        ] + self::scoreRows() + [
            '最終学歴' => function (Application $a) {
                $school = self::school($a);

                return $school ? Str::upper((string) $school->institution).(isset(self::LEVELS_JA[$school->level]) ? '（'.self::LEVELS_JA[$school->level].'）' : '') : '';
            },
            '専攻'     => fn (Application $a) => self::major(self::school($a)),
            '卒業年'   => function (Application $a) {
                $school = self::school($a);

                return match (true) {
                    ! $school           => '',
                    $school->is_current => '在学中',
                    default             => $school->end_year ? $school->end_year.'年'.($school->end_month ? $school->end_month.'月' : '') : '',
                };
            },
            '職歴'     => self::work(...),
            '来日歴'   => self::beenToJapan(...),
            '宗教'     => self::religion(...),
            '家族'     => self::family(...),
        ] + self::cvRow();
    }

    /** 写真, 番号 and the name in Latin letters and katakana: the top of every list. */
    private static function head(): array
    {
        return [
            '写真' => fn () => '',
            '番号' => fn (Application $a, int $i) => ($i + 1).(! empty($a->assessment['recommended']) ? ' ⭐' : ''),
            '名前' => fn (Application $a) => Str::upper(self::profile($a)?->full_name ?: (string) $a->user?->name),
            'kana' => fn (Application $a) => (string) self::profile($a)?->name_kana,
        ];
    }

    /** Our assessment: the overall score and the four it is made of. */
    private static function scoreRows(): array
    {
        return collect(self::SCORES)->mapWithKeys(fn ($label, $key) => [
            $label => fn (Application $a) => isset($a->assessment[$key]) && is_numeric($a->assessment[$key]) ? $a->assessment[$key] + 0 : '',
        ])->all();
    }

    /** The CV for the company: a link that works without logging in, for 60 days. */
    private static function cvRow(): array
    {
        return ['履歴書' => fn (Application $a) => $a->user ? [
            Str::upper(self::profile($a)?->full_name ?: $a->user->name),
            URL::temporarySignedRoute('cv.shared', now()->addDays(60), ['user' => $a->user]),
        ] : ''];
    }

    private static function profile(Application $a): ?\App\Models\StudentProfile
    {
        return $a->user?->studentProfile;
    }

    private static function yesNo(?bool $yes): string
    {
        return $yes === null ? '' : ($yes ? '有' : '無');
    }

    private static function age(Application $a): string
    {
        return self::profile($a)?->birthdate ? (string) self::profile($a)->birthdate->age : '';
    }

    private static function gender(Application $a): string
    {
        return self::GENDER[self::profile($a)?->gender ?? ''] ?? '';
    }

    private static function beenToJapan(Application $a): string
    {
        $p = self::profile($a);

        return self::yesNo(((int) $p?->japan_visits_study + (int) $p?->japan_visits_work + (int) $p?->japan_visits_other) > 0);
    }

    private static function religion(Application $a): string
    {
        return Rirekisho::religion(self::profile($a)?->religion) ?? (string) self::profile($a)?->religion;
    }

    private static function family(Application $a): string
    {
        return self::MARITAL[self::profile($a)?->marital_status ?? ''] ?? '';
    }

    /** The school they are at now, else the last one they finished. */
    private static function school(Application $a): ?\App\Models\StudentEducation
    {
        $educations = $a->user?->educations ?? collect();

        return $educations->firstWhere('is_current', true) ?? $educations->first();
    }

    private static function major(?\App\Models\StudentEducation $school): string
    {
        return (string) ($school?->major_ja ?: (Rirekisho::SCHOOL_STREAMS_JA[Str::upper(trim((string) $school?->major))] ?? $school?->major));
    }

    /** Newest first, one line a job: "Company　Position（2年3ヶ月）"; 無 when none. */
    private static function work(Application $a): string
    {
        $jobs = $a->user?->workExperiences ?? collect();
        if ($jobs->isEmpty()) {
            return '無';
        }

        return $jobs->take(3)->map(function ($job) {
            $months = $job->start_date ? (int) $job->start_date->diffInMonths($job->is_current || ! $job->end_date ? now() : $job->end_date) : null;
            $length = $months === null ? '' : '（'.collect([intdiv($months, 12) ? intdiv($months, 12).'年' : null, $months % 12 ? ($months % 12).'ヶ月' : null])->filter()->implode('').'）';

            return trim($job->company.'　'.($job->position_ja ?: $job->position)).($length !== '（）' ? $length : '');
        })->implode("\n");
    }

    private static function bmi($height, $weight): string
    {
        if (! $height || ! $weight) {
            return '';
        }

        return str_replace('.', ',', (string) round($weight / (($height / 100) ** 2), 1));
    }

    /**
     * The best Indonesian driving licence with the years held, and whether
     * that is enough to take the medium (2 years) or large (3 years) exam.
     *
     * @return array{0: string, 1: string}
     */
    private static function license(?array $licenses): array
    {
        $held = collect($licenses ?? [])->keyBy('type');
        foreach (self::LICENSES as $type => $ja) {
            if ($license = $held->get($type)) {
                $years = ! empty($license['year']) ? now()->year - (int) $license['year'] : null;

                return [
                    $ja.($years !== null ? ' '.$years.'年' : ''),
                    match (true) {
                        $years === null => '',
                        $years >= 3     => '〇',
                        $years >= 2     => '中型のみ',
                        default         => '×',
                    },
                ];
            }
        }

        return ['無', '×'];
    }

    private static function ssw(?array $licenses): string
    {
        $passed = collect($licenses ?? [])->where('group', 'ssw')
            ->map(fn ($l) => Certificates::GROUPS['ssw'][$l['type'] ?? ''][1] ?? null)->filter();

        return $passed->isNotEmpty() ? $passed->map(fn ($name) => $name.' 合格')->implode("\n") : '未取得';
    }

    /** The profile photo in row 1, fitted to the cell. */
    private static function photo(Worksheet $sheet, Application $application, int $col): void
    {
        $path = $application->user?->studentProfile?->photo_path;
        if (! $path || ! Storage::disk('public')->exists($path) || ! @getimagesize(Storage::disk('public')->path($path))) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setPath(Storage::disk('public')->path($path));
        $drawing->setResizeProportional(true);
        $drawing->setHeight(220);
        if ($drawing->getWidth() > 186) {
            $drawing->setWidth(186);
        }
        $drawing->setCoordinates(Coordinate::stringFromColumnIndex($col).'1');
        $drawing->setOffsetX((int) max(0, (200 - $drawing->getWidth()) / 2));
        $drawing->setOffsetY((int) max(0, (240 - $drawing->getHeight()) / 2));
        $drawing->setWorksheet($sheet);
    }

    /** Column widths, row heights and colours as in the template. */
    private static function style(Worksheet $sheet, array $labels, int $candidates, bool $numberInBlue = true): void
    {
        $last = Coordinate::stringFromColumnIndex($candidates + 1);
        $rows = count($labels);

        $sheet->getColumnDimension('A')->setWidth(40);
        foreach (range(2, $candidates + 1) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(28.5);
        }
        $sheet->getRowDimension(1)->setRowHeight(180);
        foreach (range(2, $rows) as $r) {
            $sheet->getRowDimension($r)->setRowHeight(37.5);
        }

        $all = $sheet->getStyle("A1:{$last}{$rows}");
        $all->getFont()->setBold(true)->setName('Arial')->setSize(11);
        $all->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $all->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('B7B7B7');

        if ($numberInBlue) {
            $number = $sheet->getStyle("B2:{$last}2");
            $number->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_BLUE);
            $number->getFont()->getColor()->setRGB('FFFFFF');
        }

        foreach ($labels as $r => $label) {
            if (in_array($label, self::SCORES, true) && $label !== self::SCORES['overall']) {
                $sheet->getStyle('A'.($r + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::SCORE_YELLOW);
            }
        }

        $sheet->mergeCells('A3:A4');   // 名前 over the name and its katakana
        $sheet->freezePane('B1');
    }

    /** Excel sheet names: at most 31 characters, none of : \ / ? * [ ]. */
    private static function sheetTitle(string $title): string
    {
        return Str::limit(trim(preg_replace('/[:\\\\\/?*\[\]]/', ' ', $title)), 28, '…');
    }
}
