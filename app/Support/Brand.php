<?php

namespace App\Support;

/**
 * The brand's names and logos (config/brand.php). Translations get them
 * as :brand, :company and :learning without passing them (see
 * BrandTranslator).
 */
class Brand
{
    public static function name(): string
    {
        return (string) config('brand.name');
    }

    /** The legal name: payments, the copyright, PDF authors. */
    public static function company(): string
    {
        return (string) config('brand.company');
    }

    /** The Japanese-learning platform. */
    public static function learning(): string
    {
        return (string) config('brand.learning');
    }

    /** Who built the site, for the footer credit; null when there is none. */
    public static function maker(): ?string
    {
        return filled(config('brand.maker')) ? (string) config('brand.maker') : null;
    }

    /** URL of the intro video, 'desktop' (landscape) or 'mobile' (portrait); null when there is none. */
    public static function introVideo(string $cut): ?string
    {
        $path = config("brand.intro.{$cut}");

        return $path && is_file(public_path($path)) ? asset($path) : null;
    }

    /** @return array{brand: string, company: string, learning: string} */
    public static function replacements(): array
    {
        return ['brand' => self::name(), 'company' => self::company(), 'learning' => self::learning()];
    }

    /**
     * A logo in public/images/brand: 'mark' (the fox alone), 'horizontal'
     * (fox and name side by side) or 'stacked' (name under the fox), in
     * the version for a light or a dark background.
     */
    public static function logo(string $kind = 'horizontal', string $on = 'light'): string
    {
        return asset($kind === 'mark' ? 'images/brand/mark.png' : "images/brand/{$kind}-on-{$on}.png");
    }
}
