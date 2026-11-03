<?php

namespace App\Support;

use App\Models\Company;
use App\Models\JobCategory;
use App\Models\VisaCategory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns a job order in any layout (PDF from a TSK, Japanese, English or
 * Indonesian) into the job form: an AI reads the document and answers
 * with JSON in our Job Order fields (App\Support\JobOrder). The AI is
 * either Gemini through its API (services.gemini) or any chat AI the
 * admin pastes the prompt into; both answers go through toFormData().
 */
class JobOrderImport
{
    /** The instructions for the AI, built from the Job Order fields so the two never drift apart. */
    public static function prompt(): string
    {
        $visas = VisaCategory::orderBy('sort_order')->get()->map(fn ($v) => $v->t('name', 'id'))->all();
        $categories = JobCategory::orderBy('sort_order')->get()->map(fn ($c) => $c->t('name', 'id'))->all();

        $jobOrder = [];
        foreach (JobOrder::sections() as $key => $section) {
            foreach ($section['fields'] as $name => $def) {
                [$label, $jp, $type] = $def;
                $hint = "{$label} ({$jp})";
                $jobOrder[$key][$name] = match ($type) {
                    'select'    => "{$hint}: salah satu dari ".self::choices(JobOrder::EMPLOYMENT).', atau null',
                    'checklist' => "{$hint}: daftar berisi kode dari ".self::choices(JobOrder::INSURANCE).', atau []',
                    default     => $hint.(isset($def[3]) ? ', contoh: '.$def[3] : ''),
                };
                if ($type === 'checklist') {
                    $jobOrder[$key]['insurance_note'] = 'Catatan asuransi lain, atau null';
                }
            }
        }

        $template = json_encode([
            'title'        => 'Judul pekerjaan singkat dalam Bahasa Indonesia, contoh: Pengemudi Truk',
            'company_name' => 'Nama perusahaan penerima seperti tertulis di dokumen, atau "Dirahasiakan"',
            'visa'         => 'Salah satu dari: '.implode(', ', array_map(fn ($v) => "\"{$v}\"", $visas)).', atau null',
            'job_category' => 'Salah satu dari: '.implode(', ', array_map(fn ($c) => "\"{$c}\"", $categories)).', atau null',
            'city'         => 'Kota penempatan, contoh: Hamamatsu',
            'prefecture'   => 'Prefektur penempatan, contoh: Shizuoka',
            'positions'    => 'Jumlah orang yang dicari (angka), atau null',
            'job_order'    => $jobOrder,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
        Kamu membantu PT Passion Japan Indonesia menyalin job order (lowongan kerja di Jepang) ke formulir kami untuk calon pekerja dari Indonesia. Baca dokumen job order yang dilampirkan (bisa berbahasa Jepang, Inggris, atau Indonesia), lalu balas HANYA dengan satu objek JSON sesuai format di bawah, tanpa penjelasan apa pun.

        Aturan:
        - Tulis semua isi dalam Bahasa Indonesia yang singkat dan jelas. Istilah Jepang yang penting boleh ditambahkan dalam kurung.
        - Isi hanya dari dokumen. Jika informasinya tidak ada, isi null. Jangan menebak atau mengarang.
        - Lewati hal yang tidak dibutuhkan calon pekerja: nama direktur, modal perusahaan, tanggal tutup buku gaji, batas waktu untuk agen/TSK, komisi, catatan internal atau marketing.
        - Uang ditulis seperti "¥180.000 / bulan" (titik sebagai pemisah ribuan); rentang seperti "¥178.000 – ¥193.000 / bulan".
        - Jika satu isian berisi beberapa hal, tulis satu hal per baris dan awali setiap baris dengan "- " (gunakan \\n sebagai pemisah baris di dalam JSON).
        - Jika nama perusahaan dirahasiakan, tulis "Dirahasiakan".

        Format JSON (ganti setiap keterangan dengan isinya):
        {$template}
        PROMPT;
    }

    /**
     * The AI's answer as job form state: dotted paths (title.id,
     * job_order.pay.basic_salary…) to values, only for what it filled in.
     * "company" names a company that is not registered yet.
     *
     * @return array{fields: array<string, mixed>, company: ?string}
     */
    public static function toFormData(string $answer): array
    {
        $data = self::decode($answer);
        $fields = [];
        $put = function (string $path, $value) use (&$fields) {
            if (filled($value)) {
                $fields[$path] = $value;
            }
        };

        $put('title.id', self::text($data['title'] ?? null));
        $put('location_city', self::text($data['city'] ?? null));
        $put('location_prefecture', self::text($data['prefecture'] ?? null));
        if (is_numeric($data['positions'] ?? null) && (int) $data['positions'] > 0) {
            $fields['positions'] = (int) $data['positions'];
        }
        $put('visa_category_id', self::matchName(VisaCategory::all(), $data['visa'] ?? null));
        $put('job_category_id', self::matchName(JobCategory::all(), $data['job_category'] ?? null));

        foreach (JobOrder::sections() as $key => $section) {
            foreach ($section['fields'] as $name => [$label, $jp, $type]) {
                $value = $data['job_order'][$key][$name] ?? null;
                $put("job_order.{$key}.{$name}", match ($type) {
                    'select'    => self::matchOption(JobOrder::EMPLOYMENT, $value),
                    'checklist' => array_values(array_filter(array_map(fn ($v) => self::matchOption(JobOrder::INSURANCE, $v), (array) $value))) ?: null,
                    default     => self::text($value),
                });
                if ($type === 'checklist') {
                    $put("job_order.{$key}.insurance_note", self::text($data['job_order'][$key]['insurance_note'] ?? null));
                }
            }
        }

        // The company: a registered one is picked; an unknown or hidden name stays in the Job Order only.
        $company = self::text($data['company_name'] ?? null);
        $unknown = null;
        if ($company && ! Str::contains(Str::lower($company), ['dirahasiakan', 'rahasia', '非公開'])) {
            $found = self::findCompany($company);
            $found ? $fields['company_id'] = $found : $unknown = $company;
        }

        return ['fields' => $fields, 'company' => $unknown];
    }

    public static function geminiEnabled(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Ask Gemini to read the file (PDF or a photo of one) and return its
     * JSON answer. Errors come back as a RuntimeException whose message can
     * be shown to the admin.
     */
    public static function readWithGemini(string $path, string $mime): string
    {
        if (! self::geminiEnabled()) {
            throw new RuntimeException(__('Automatic reading is not set up. Use "Paste AI result" instead.'));
        }

        try {
            // Interactions API (ai.google.dev): file first, then the instructions.
            $response = Http::withHeaders([
                'x-goog-api-key' => config('services.gemini.key'),
                'Api-Revision'   => '2026-05-20',
            ])
                ->timeout(180)
                ->post('https://generativelanguage.googleapis.com/v1beta/interactions', [
                    'model' => config('services.gemini.model'),
                    'input' => [
                        ['type' => str_starts_with($mime, 'image/') ? 'image' : 'document', 'data' => base64_encode(file_get_contents($path)), 'mime_type' => $mime],
                        ['type' => 'text', 'text' => self::prompt()],
                    ],
                    'response_format' => ['type' => 'text', 'mime_type' => 'application/json'],
                ]);
        } catch (ConnectionException $e) {
            report($e);
            throw new RuntimeException(__('Could not reach Gemini. Please try again, or use "Paste AI result".'));
        }

        if (! $response->successful()) {
            report(new RuntimeException('Gemini '.$response->status().': '.Str::limit($response->body(), 500)));
            throw new RuntimeException(match ($response->status()) {
                429      => __('Gemini\'s free quota is used up for now. Try again in a few minutes, or use "Paste AI result".'),
                400, 401, 403 => __('Gemini refused the request. Check GEMINI_API_KEY and GEMINI_MODEL in .env, or use "Paste AI result".'),
                default  => __('Gemini could not read the file. Please try again, or use "Paste AI result".'),
            });
        }

        $text = $response->json('output_text') ?? self::firstText($response->json());
        if (blank($text)) {
            throw new RuntimeException(__('Gemini could not read the file. Please try again, or use "Paste AI result".'));
        }

        return $text;
    }

    /** The JSON object in an AI reply, which may come wrapped in ```json fences or a sentence. */
    private static function decode(string $answer): array
    {
        $start = strpos($answer, '{');
        $end = strrpos($answer, '}');
        $data = $start !== false && $end > $start ? json_decode(substr($answer, $start, $end - $start + 1), true) : null;

        if (! is_array($data)) {
            throw new RuntimeException(__('The AI reply could not be read. Copy the whole reply (it starts with { and ends with }) and try again.'));
        }

        return $data;
    }

    /** A value as one trimmed string: lists become "- " lines, null-ish words become null. */
    private static function text(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = collect($value)->flatten()->filter(fn ($v) => filled($v))
                ->map(fn ($v) => '- '.ltrim(trim((string) $v), '-• '))->join("\n");
        }
        if (is_bool($value) || $value === null) {
            return null;
        }
        $value = trim(str_replace('\n', "\n", (string) $value));

        return in_array(Str::lower($value), ['', 'null', '-', '—', 'n/a', 'tidak ada informasi'], true) ? null : $value;
    }

    /** The id of the visa or job category whose Indonesian name (or slug) matches. */
    private static function matchName($models, mixed $name): ?int
    {
        $name = Str::lower(trim((string) $name));
        if ($name === '' || $name === 'null') {
            return null;
        }

        return $models->first(fn ($m) => Str::lower($m->t('name', 'id')) === $name || $m->slug === Str::slug($name))?->id;
    }

    /** An option key from its key or its label ("seishain" or "Karyawan Tetap"). */
    private static function matchOption(array $options, mixed $value): ?string
    {
        $value = Str::lower(trim((string) $value));
        if ($value === '') {
            return null;
        }
        foreach ($options as $key => $label) {
            if ($value === $key || Str::contains(Str::lower($label), $value) || Str::contains($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    /** A registered company whose name or Japanese name matches, ignoring "Co., Ltd." and the like. */
    private static function findCompany(string $name): ?int
    {
        $plain = fn (?string $n) => trim(preg_replace('/\b(co\.?,?\s*ltd\.?|inc\.?|corp\.?|pt\.?|kabushiki\s+kaisha|k\.?k\.?)|株式会社|有限会社|合同会社|[\s.,()（）]+/iu', '', Str::lower((string) $n)));
        $wanted = $plain($name);
        if ($wanted === '') {
            return null;
        }

        return Company::all()->first(fn (Company $c) => in_array($wanted, array_filter([$plain($c->name), $plain($c->name_ja)]), true))?->id;
    }

    /** The first "text" anywhere in a response, for answers shaped differently than expected. */
    private static function firstText(mixed $node): ?string
    {
        if (is_array($node)) {
            if (is_string($node['text'] ?? null) && str_contains($node['text'], '{')) {
                return $node['text'];
            }
            foreach ($node as $child) {
                if ($found = self::firstText($child)) {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function choices(array $options): string
    {
        return implode(', ', array_map(fn ($k, $l) => "\"{$k}\" ({$l})", array_keys($options), $options));
    }
}
