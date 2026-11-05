<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('site.locales', []));
        $fallback  = config('site.default_locale', config('app.locale', 'id'));

        // Indonesian until the visitor picks a language; the browser's language is not used.
        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? optional($request->user())->locale
            ?? $fallback;

        if (! in_array($locale, $supported, true)) {
            $locale = $fallback;
        }

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
