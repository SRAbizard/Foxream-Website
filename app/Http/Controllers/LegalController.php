<?php

namespace App\Http\Controllers;

use App\Support\Legal;
use Illuminate\Contracts\View\View;

/**
 * Privacy policy and terms & conditions.
 * Content comes from Admin → Legal (App\Support\Legal).
 */
class LegalController extends Controller
{
    public function privacy(): View
    {
        return $this->document('privacy', __('Privacy Policy'), __('How we collect, use and protect your personal data.'));
    }

    public function terms(): View
    {
        return $this->document('terms', __('Terms & Conditions'), __('The rules for using this website and our services.'));
    }

    private function document(string $doc, string $title, string $subtitle): View
    {
        return view('pages.legal', [
            'doc'       => $doc,
            'title'     => $title,
            'subtitle'  => $subtitle,
            'body'      => Legal::document($doc),
            'updatedAt' => \Illuminate\Support\Carbon::parse(Legal::updatedAt($doc)),
        ]);
    }
}
