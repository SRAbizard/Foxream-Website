<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Groups free-text vacancy locations into Japan's eight regions
 * (config/site.php → regions) for the homepage map and the
 * ?region= filter on /jobs.
 */
class JapanRegions
{
    public static function all(): array
    {
        return config('site.regions', []);
    }

    public static function get(?string $key): ?array
    {
        return $key ? (self::all()[$key] ?? null) : null;
    }

    /**
     * Region key for a vacancy's prefecture/city, or null when unknown.
     * Accepts "Aichi", "Aichi-ken", "Aichi Prefecture", "Tokyo-to", …
     */
    public static function keyFor(?string $prefecture, ?string $city = null): ?string
    {
        $pref = self::normalize($prefecture);
        $town = self::normalize($city);

        foreach (self::all() as $key => $region) {
            $prefs = array_map([self::class, 'normalize'], $region['prefectures']);
            if ($pref && in_array($pref, $prefs, true)) {
                return $key;
            }
        }
        foreach (self::all() as $key => $region) {
            $names = array_map([self::class, 'normalize'], [...$region['prefectures'], ...$region['cities']]);
            if ($town && in_array($town, $names, true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Constrain a JobVacancy query to one region.
     */
    public static function scope(Builder $query, string $key): Builder
    {
        $region = self::get($key);
        if (! $region) {
            return $query;
        }

        $names = [...$region['prefectures'], ...$region['cities']];

        return $query->where(function (Builder $q) use ($region, $names) {
            foreach ($region['prefectures'] as $pref) {
                $q->orWhere('location_prefecture', 'like', $pref.'%');
            }
            $q->orWhere(function (Builder $q) use ($names) {
                $q->where(fn (Builder $q) => $q->whereNull('location_prefecture')->orWhere('location_prefecture', ''))
                  ->whereIn('location_city', $names);
            });
        });
    }

    private static function normalize(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return (string) Str::of($value)->lower()->trim()
            ->replaceMatches('/\s*prefecture$/', '')
            ->replaceMatches('/-(ken|fu|to|do)$/', '');
    }
}
