<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Sign in as a demo account from the switcher, on a local install only. */
class DemoLoginController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless(DemoAccounts::enabled() && DemoAccounts::isDemo($user), 404);

        // A demo email cannot receive the verification link.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Back to the same panel page when it fits the account, else its home.
        $back = (string) $request->query('back');
        $panel = $user->hasRole('student') ? '/dashboard' : '/admin';

        return redirect(str_starts_with(parse_url($back, PHP_URL_PATH) ?? '', $panel) ? $back : url($panel));
    }
}
