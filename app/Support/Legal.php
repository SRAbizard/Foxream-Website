<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\HtmlString;

/**
 * Privacy policy, terms, anti-fraud notice and programme fees — all
 * editable in Admin → Legal & Fees. Anything left empty falls back to
 * the bundled defaults (resources/views/legal/defaults), so the pages
 * are never blank.
 */
class Legal
{
    public const LOCALES = ['id', 'en', 'ja'];

    /** Privacy policy or terms as sanitised HTML, in the current locale. */
    public static function document(string $doc): HtmlString
    {
        $stored = (array) Setting::get("legal.{$doc}", []);
        $html   = self::pick($stored) ?? self::defaultDocument($doc);

        return SafeHtml::clean($html);
    }

    /** The bundled default text, used to prefill the admin editor too. */
    public static function defaultDocument(string $doc, ?string $locale = null): string
    {
        $locale = in_array($locale ??= app()->getLocale(), self::LOCALES, true) ? $locale : 'id';

        return view("legal.defaults.{$doc}-{$locale}", self::companyFacts())->render();
    }

    /** When the document was last changed (admin save), else the launch date. */
    public static function updatedAt(string $doc): string
    {
        return Setting::get("legal.{$doc}_updated_at") ?? '2026-10-26';
    }

    /**
     * Anti-fraud warning points for the current locale, one per line in
     * the admin field.
     *
     * @return array<int, string>
     */
    public static function fraudPoints(): array
    {
        $stored = self::pick((array) Setting::get('legal.fraud_points', []));
        $text   = $stored ?? implode("\n", self::defaultFraudPoints());

        return array_values(array_filter(array_map('trim', explode("\n", $text))));
    }

    /** @return array<int, string> */
    public static function defaultFraudPoints(?string $locale = null): array
    {
        return match ($locale ?? app()->getLocale()) {
            'en' => [
                \App\Support\Brand::name().' never promises to pass an interview or get a visa in exchange for money.',
                'Official payments go only to a bank account in the name of '.\App\Support\Brand::company().', always with an official receipt.',
                'We never ask you to transfer money to a staff member\'s or anyone else\'s personal account.',
                'Official information comes only from this website and the official channels listed here.',
                'In doubt? Contact us before you pay anything.',
            ],
            'ja' => [
                \App\Support\Brand::name().'が金銭と引き換えに面接合格やビザ取得を約束することは一切ありません。',
                '正規のお支払いは「'.\App\Support\Brand::company().'」名義の口座のみで、必ず正式な領収書を発行します。',
                '担当者個人やその他の個人名義の口座への振込をお願いすることはありません。',
                '公式情報は当ウェブサイトと、ここに記載の公式窓口からのみ発信しています。',
                '不審に思ったら、お支払いの前に必ずご連絡ください。',
            ],
            default => [
                \App\Support\Brand::name().' tidak pernah menjanjikan kelulusan interview atau visa dengan imbalan uang.',
                'Pembayaran resmi hanya ke rekening atas nama '.\App\Support\Brand::company().', dan selalu disertai kuitansi resmi.',
                'Kami tidak pernah meminta transfer ke rekening pribadi staf atau pihak lain.',
                'Informasi resmi hanya dari website ini dan kanal resmi kami yang tercantum di sini.',
                'Ragu? Hubungi kami dulu sebelum membayar apa pun.',
            ],
        };
    }

    /**
     * Official channels shown under the fraud notice.
     *
     * @return array<int, array{label: string, value: string, url: ?string}>
     */
    public static function officialChannels(): array
    {
        $facts = self::companyFacts();
        $wa    = Setting::get('contact.whatsapp', config('passion.contact.whatsapp'));

        return array_values(array_filter([
            ['label' => __('Website'), 'value' => preg_replace('~^https?://~', '', rtrim(config('app.url'), '/')), 'url' => url('/')],
            $facts['email'] ? ['label' => __('Email'), 'value' => $facts['email'], 'url' => 'mailto:'.$facts['email']] : null,
            $wa ? ['label' => 'WhatsApp', 'value' => $facts['phone'] ?: '+'.$wa, 'url' => 'https://wa.me/'.preg_replace('/\D/', '', $wa)] : null,
        ]));
    }

    /**
     * Programme fee rows in the current locale.
     *
     * @return array<int, array{name: string, amount: string, note: ?string}>
     */
    public static function fees(): array
    {
        $locale = app()->getLocale();

        return collect((array) Setting::get('legal.fees', []))
            ->map(fn ($f) => [
                'name'   => ($f["name_{$locale}"] ?? null) ?: ($f['name_id'] ?? ''),
                'amount' => (string) ($f['amount'] ?? ''),
                'note'   => ($f["note_{$locale}"] ?? null) ?: ($f['note_id'] ?? null),
            ])
            ->filter(fn ($f) => $f['name'] !== '')
            ->values()
            ->all();
    }

    /** Free-text note under the fee table, current locale. */
    public static function feesNote(): ?string
    {
        return self::pick((array) Setting::get('legal.fees_note', []));
    }

    /** Company details used inside the default texts. */
    public static function companyFacts(): array
    {
        $offices = (array) Setting::get('contact.offices', config('passion.contact.offices', []));

        return [
            'company' => \App\Support\Brand::company(),
            'email'   => Setting::get('contact.email', config('passion.contact.email')),
            'phone'   => Setting::get('contact.phone', config('passion.contact.phone')),
            'offices' => collect($offices)->map(fn ($o) => $o['address'] ?? trim(($o['city'] ?? '').', '.($o['country'] ?? ''), ', '))->filter()->values()->all(),
            'site'    => url('/'),
        ];
    }

    /** Current-locale value from an {id, en, ja} array, falling back to id. */
    private static function pick(array $values): ?string
    {
        $value = $values[app()->getLocale()] ?? null;
        $value = filled(strip_tags((string) $value)) ? $value : ($values['id'] ?? null);

        return filled(strip_tags((string) $value)) ? $value : null;
    }
}
