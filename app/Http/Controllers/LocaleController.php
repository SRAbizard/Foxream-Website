<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supported = array_keys(config('site.locales', []));

        if (in_array($locale, $supported, true)) {
            $request->session()->put('locale', $locale);

            if ($user = $request->user()) {
                $user->forceFill(['locale' => $locale])->save();
            }
        }

        return redirect()->back();
    }
}
