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
