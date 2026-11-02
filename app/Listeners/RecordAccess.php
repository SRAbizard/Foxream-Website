<?php

namespace App\Listeners;

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

/**
 * Sign-in events for the access history, and the user's last login.
 * Found by Laravel's event discovery (one handle method per event).
 */
class RecordAccess
{
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            AccessLog::record('login', null, $event->user->id);
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            AccessLog::record('logout', null, $event->user->id);
        }
    }

    /** The e-mail typed, never the password. */
    public function handleFailed(Failed $event): void
    {
        AccessLog::record('login_failed', $event->credentials['email'] ?? null, $event->user?->getAuthIdentifier());
    }

    public function handleRegistered(Registered $event): void
    {
        AccessLog::record('registered', null, $event->user->getAuthIdentifier());
    }

    public function handleVerified(Verified $event): void
    {
        AccessLog::record('email_verified', null, $event->user->getAuthIdentifier());
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        AccessLog::record('password_reset', null, $event->user->getAuthIdentifier());
    }
}
