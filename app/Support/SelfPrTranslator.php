<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Translates a student's self-introduction into the Japanese 自己PR printed
 * on the CV, through the DeepL API (config: services.deepl). Errors come
 * back as a RuntimeException whose message can be shown to the student.
 */
final class SelfPrTranslator
{
    public static function enabled(): bool
    {
        return filled(config('services.deepl.key'));
    }

    public static function translate(string $text): string
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
                ->post($host.'/v2/translate', [
                    'text'                => [trim($text)],
                    'target_lang'         => 'JA',
                    'formality'           => 'prefer_more',   // です・ます, as on a 履歴書
                    'preserve_formatting' => true,
                ]);
        } catch (ConnectionException $e) {
            report($e);
            throw new RuntimeException(__('Translation failed. Please try again.'));
        }

        if ($response->successful()) {
            $japanese = trim((string) $response->json('translations.0.text'));
            if ($japanese !== '') {
                return $japanese;
            }
        }

        throw new RuntimeException(match ($response->status()) {
            429     => __('The translator is busy. Please try again in a minute.'),
            456     => __('This month\'s translation quota is used up. Please try again next month or tell the admin.'),
            401, 403 => __('Translation is not set up correctly. Please tell the admin.'),
            default => __('Translation failed. Please try again.'),
        });
    }
}
