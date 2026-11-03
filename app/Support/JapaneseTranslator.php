<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Translates student text (自己PR, job titles) into Japanese for the CV
 * through the DeepL API (config: services.deepl). Errors come back as a
 * RuntimeException whose message can be shown to the student.
 */
final class JapaneseTranslator
{
    public static function enabled(): bool
    {
        return filled(config('services.deepl.key'));
    }

    public static function translate(string $text): string
    {
        return self::many([$text])[0];
    }

    /**
     * @param  list<string>  $texts
     * @param  string  $target  DeepL target language, e.g. JA or EN-US
     * @param  bool  $html  the texts are HTML (rich editor content), so tags are kept
     * @return list<string> in the same order
     */
    public static function many(array $texts, string $target = 'JA', bool $html = false): array
    {
        if (! self::enabled()) {
            throw new RuntimeException(__('Translation is not set up yet. Please tell the admin.'));
        }

        $key = config('services.deepl.key');
        // DeepL API Free keys end in ":fx" and use their own host.
        $host = Str::endsWith($key, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';

        try {
            $response = Http::withHeaders(['Authorization' => 'DeepL-Auth-Key '.$key])
                ->timeout(30)
                ->post($host.'/v2/translate', array_filter([
                    'text'                => array_map('trim', array_values($texts)),
                    'target_lang'         => $target,
                    'formality'           => 'prefer_more',   // です・ます, as on a 履歴書
                    'preserve_formatting' => true,
                    'tag_handling'        => $html ? 'html' : null,
                ]));
        } catch (ConnectionException $e) {
            report($e);
            throw new RuntimeException(__('Translation failed. Please try again.'));
        }

        if ($response->successful()) {
            $out = array_map(fn ($t) => trim((string) ($t['text'] ?? '')), $response->json('translations') ?? []);
            if (count($out) === count($texts) && ! in_array('', $out, true)) {
                return $out;
            }
        }

        throw new RuntimeException(match ($response->status()) {
            429      => __('The translator is busy. Please try again in a minute.'),
            456      => __('This month\'s translation quota is used up. Please try again next month or tell the admin.'),
            401, 403 => __('Translation is not set up correctly. Please tell the admin.'),
            default  => __('Translation failed. Please try again.'),
        });
    }
}
