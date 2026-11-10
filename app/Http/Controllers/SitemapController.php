<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\JobVacancy;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $locales = array_keys(config('site.locales', []));
        $now     = now()->toAtomString();

        $urls = [
            ['loc' => url('/'),        'priority' => '1.0', 'changefreq' => 'weekly',  'lastmod' => $now],
            ['loc' => url('/about'),   'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $now],
            ['loc' => url('/contact'), 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $now],
            ['loc' => url('/blog'),    'priority' => '0.7', 'changefreq' => 'daily',   'lastmod' => $now],
            ['loc' => url('/event'),   'priority' => '0.7', 'changefreq' => 'daily',   'lastmod' => $now],
            ['loc' => url('/jobs'),      'priority' => '0.8', 'changefreq' => 'daily',   'lastmod' => $now],
            ['loc' => url('/gallery'),   'priority' => '0.6', 'changefreq' => 'weekly',  'lastmod' => $now],
            ['loc' => url('/services'),  'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $now],
            ['loc' => url('/privacy-policy'), 'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => $now],
            ['loc' => url('/terms'),          'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => $now],
        ];

        foreach (Post::published()->latest('published_at')->get() as $p) {
            $urls[] = [
                'loc'        => url('/blog/'.$p->slug),
                'priority'   => '0.6',
                'changefreq' => 'monthly',
                'lastmod'    => optional($p->updated_at)->toAtomString() ?? $now,
            ];
        }
        foreach (Event::published()->orderByDesc('starts_at')->get() as $e) {
            $urls[] = [
                'loc'        => url('/event/'.$e->slug),
                'priority'   => '0.5',
                'changefreq' => 'weekly',
                'lastmod'    => optional($e->updated_at)->toAtomString() ?? $now,
            ];
        }
        foreach (JobVacancy::published()->orderByDesc('published_at')->get() as $v) {
            $urls[] = [
                'loc'        => url('/jobs/'.$v->slug),
                'priority'   => '0.7',
                'changefreq' => 'weekly',
                'lastmod'    => optional($v->updated_at)->toAtomString() ?? $now,
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$u['loc']}</loc>\n";
            $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            foreach ($locales as $locale) {
                $alt = $u['loc'].'?lang='.$locale;
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$locale}\" href=\"{$alt}\"/>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
