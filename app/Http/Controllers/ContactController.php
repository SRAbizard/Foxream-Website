<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\JobCategory;
use App\Models\User;
use App\Models\VisaCategory;
use App\Notifications\NewContactMessageNotification;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * /contact — one page for candidates and for companies in Japan (?for=employer
 * opens the company side). Both forms are saved as contact messages, so they
 * show up in Admin → Contact Messages and notify the admins; a company's
 * subject starts with [Worker request] and its name.
 */
class ContactController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.contact', [
            'for'        => session('for') ?? old('for') ?? ($request->query('for') === 'employer' ? 'employer' : 'candidate'),
            'categories' => JobCategory::orderBy('sort_order')->get(),
            'visas'      => VisaCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $thanks = __('Thanks! Your message has been sent — we will get back to you within 24 hours.');

        // Bots get the normal success message but nothing is stored.
        if (SpamGuard::isBot($request)) {
            return back()->with('status', $thanks)->withFragment('contact-form');
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'string', 'email:rfc', 'max:191'],
            'phone'   => ['nullable', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $this->save($request, $data);

        return back()->with('status', $thanks)->withFragment('contact-form');
    }

    /** A company asking for workers. */
    public function employer(Request $request): RedirectResponse
    {
        $thanks = __('Thank you! Our team will contact you within 1–2 business days.');

        if (SpamGuard::isBot($request)) {
            return back()->with(['status' => $thanks, 'for' => 'employer'])->withFragment('contact-form');
        }

        $data = $request->validate([
            'company'  => ['required', 'string', 'max:160'],
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'string', 'email:rfc', 'max:191'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'field'    => ['nullable', 'string', 'max:120'],
            'visa'     => ['nullable', 'string', 'max:120'],
            'workers'  => ['nullable', 'integer', 'min:1', 'max:999'],
            'start'    => ['nullable', 'string', 'max:120'],
            'message'  => ['nullable', 'string', 'max:5000'],
        ]);

        $lines = collect([
            __('Company')         => $data['company'],
            __('Location')        => $data['location'] ?? null,
            __('Field')           => $data['field'] ?? null,
            __('Visa route')      => $data['visa'] ?? null,
            __('Workers needed')  => $data['workers'] ?? null,
            __('Preferred start') => $data['start'] ?? null,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        $this->save($request, [
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'subject' => '['.__('Worker request').'] '.$data['company'],
            'message' => trim($lines."\n\n".($data['message'] ?? '')),
        ]);

        return back()->with(['status' => $thanks, 'for' => 'employer'])->withFragment('contact-form');
    }

    private function save(Request $request, array $data): void
    {
        $contact = ContactMessage::create([
            ...$data,
            'locale'     => app()->getLocale(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        $admins = User::role(['superadmin', 'admin'])->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewContactMessageNotification($contact));
        }
    }
}
