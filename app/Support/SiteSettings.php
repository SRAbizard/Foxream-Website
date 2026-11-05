<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Single read-side surface for everything edited under Admin → Website
 * Settings. Each accessor reads the Setting model first, falls back to
 * config/site.php so the public site keeps working before any value
 * has been overridden.
 */
class SiteSettings
{
    /**
     * Contact value (email, phone, whatsapp).
     */
    public static function contact(string $key): ?string
    {
        return Setting::get("contact.{$key}", config("site.contact.{$key}"));
    }

    /**
     * Social media URL (instagram, facebook, tiktok).
     */
    public static function social(string $key): ?string
    {
        return Setting::get("social.{$key}", config("site.contact.{$key}"));
    }

    /**
     * Stat value (students, workers, companies).
     */
    public static function stat(string $key): ?string
    {
        return Setting::get("stats.{$key}", config("site.stats.{$key}"));
    }

    /**
     * Office list. Each entry: {city, country, address, maps_url}
     * If maps_url is empty, an auto-generated Google Maps search URL
     * for the address is returned in its place.
     *
     * @return array<int, array{city: ?string, country: ?string, address: ?string, maps_url: string}>
     */
    public static function offices(): array
    {
        $stored = Setting::get('contact.offices', null);
        $offices = is_array($stored) ? $stored : config('site.contact.offices', []);

        return collect($offices)
            ->filter(fn ($o) => filled($o['city'] ?? null) || filled($o['address'] ?? null))
            ->map(fn ($o) => [
                'city'     => $o['city']     ?? null,
                'country'  => $o['country']  ?? null,
                'address'  => $o['address']  ?? null,
                'maps_url' => filled($o['maps_url'] ?? null)
                    ? $o['maps_url']
                    : static::mapsUrlFor($o['address'] ?? trim(($o['city'] ?? '').', '.($o['country'] ?? ''), ', ')),
            ])
            ->values()
            ->all();
    }

    /**
     * Google Maps search URL for an arbitrary address string.
     */
    public static function mapsUrlFor(string $address): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($address);
    }

    /**
     * Public URL for the uploaded Company Profile document, or null if
     * none has been uploaded yet.
     */
    public static function companyProfileUrl(): ?string
    {
        $path = Setting::get('company.profile_pdf_path');
        return $path ? asset('storage/'.$path) : null;
    }

    /**
     * Homepage scrolling band phrases in the current locale (falling back to
     * Indonesian), or null when the admin left the band empty — the view
     * then uses its automatic text. $band is "red" or "ink".
     *
     * @return list<string>|null
     */
    public static function marquee(string $band): ?array
    {
        $items = Setting::get("homepage.marquee_{$band}", []);
        if (! is_array($items) || empty($items)) {
            return null;
        }
        $locale = app()->getLocale();
        $out = collect($items)
            ->map(fn ($i) => trim((string) (($i[$locale] ?? '') ?: ($i['id'] ?? ''))))
            ->filter()
            ->values()
            ->all();

        return $out ?: null;
    }

    /**
     * The learning platform's URL (config/brand.php names it), or null
     * while it is not online yet — callers hide the E-Learning link then.
     */
    public static function academyUrl(): ?string
    {
        $url = Setting::get('academy.url', config('site.academy.url'));
        return filled($url) ? $url : null;
    }

    /**
     * Returns the company-profile video URL — either the uploaded mp4 or
     * the admin-pasted YouTube embed. Returns ['type' => 'file'|'youtube',
     * 'url' => string, 'poster' => ?string] for the blade to switch on,
     * or null when neither is configured.
     */
    public static function companyVideo(): ?array
    {
        $path = Setting::get('company.video_path');
        $yt   = Setting::get('company.video_youtube_url');
        $poster = Setting::get('company.video_poster_path');
        $posterUrl = $poster ? asset('storage/'.$poster) : null;

        if ($path) {
            return ['type' => 'file', 'url' => asset('storage/'.$path), 'poster' => $posterUrl];
        }
        if ($yt) {
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $yt, $m)) {
                return [
                    'type'    => 'youtube',
                    'url'     => "https://www.youtube.com/embed/{$m[1]}?rel=0&autoplay=1",
                    'poster'  => $posterUrl ?: "https://img.youtube.com/vi/{$m[1]}/maxresdefault.jpg",
                    'video_id'=> $m[1],
                ];
            }
            return ['type' => 'youtube', 'url' => $yt, 'poster' => $posterUrl, 'video_id' => null];
        }
        return null;
    }

    /**
     * Public URL for the background theme song. Falls back to the
     * bundled "Ishikari Lore" (koto & flute) when none is uploaded.
     * Plays as muted-autoplay until the visitor turns it on.
     */
    public static function themeAudioUrl(): string
    {
        $path = Setting::get('audio.theme_path');
        return self::uploaded($path) ? asset('storage/'.$path) : asset('audio/ishikari-lore.mp3');
    }

    /**
     * Credit line for the bundled theme song (its CC BY 4.0 licence
     * requires attribution), or null when the admin uploaded their own.
     */
    public static function themeAudioCredit(): ?string
    {
        return self::uploaded(Setting::get('audio.theme_path'))
            ? null
            : '“Ishikari Lore” — Kevin MacLeod (incompetech.com), CC BY 4.0';
    }

    /** True when $path is a file that still exists on the public disk. */
    private static function uploaded(mixed $path): bool
    {
        return is_string($path) && $path !== ''
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
    }

    /**
     * Public URL for the login page wallpaper. Falls back to a
     * tasteful default Japan photo (Mt Fuji + sakura, from Unsplash)
     * when the admin hasn't uploaded one, so the login page is never
     * blank.
     */
    public static function loginBackgroundUrl(): string
    {
        $path = Setting::get('login.background_path');
        if (self::uploaded($path)) {
            return asset('storage/'.$path);
        }
        // Default: Mt Fuji framed by sakura (Unsplash, free to use)
        return asset('images/login/japan.jpg');
    }

    /**
     * Hero background photos — the admin can upload up to 4. When none
     * are uploaded (or every upload is gone from the disk), the three
     * bundled Japan photos in public/images/hero are shown instead.
     *
     * @return array<int, array{image: string}>
     */
    public static function heroSlides(): array
    {
        $uploaded = collect(Setting::get('hero.slides', []) ?: [])
            ->pluck('image_path')
            ->filter(fn ($path) => is_string($path) && $path !== ''
                && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
            ->take(4)
            ->map(fn (string $path) => ['image' => asset('storage/'.$path)])
            ->values()
            ->all();

        if ($uploaded) {
            return $uploaded;
        }

        // Unsplash photos (free to use): Mt Fuji & Chureito pagoda,
        // Yasaka pagoda in Kyoto, Tokyo Tower skyline.
        return array_map(
            fn (int $i) => ['image' => asset("images/hero/japan-{$i}.jpg")],
            [1, 2, 3],
        );
    }
}
