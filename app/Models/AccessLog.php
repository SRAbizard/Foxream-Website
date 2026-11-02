<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the access history (Admin → Access history), as in PJI
 * Learning: who did what, when, from which IP and device.
 */
class AccessLog extends Model
{
    public const UPDATED_AT = null;

    /** Actions shown highlighted: possible misuse. */
    public const WARNINGS = ['login_failed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> action => label in the current language */
    public static function labels(): array
    {
        return [
            'login'             => __('Login'),
            'logout'            => __('Logout'),
            'login_failed'      => __('Failed login'),
            'registered'        => __('Account created'),
            'email_verified'    => __('Email verified'),
            'password_reset'    => __('Password reset'),
            'profile_saved'     => __('Profile saved'),
            'cv_downloaded'     => __('CV downloaded'),
            'visa_requested'    => __('Visa target requested'),
            'document_uploaded' => __('Document uploaded'),
            'job_applied'       => __('Applied for a job'),
            'contract_signed'   => __('Signed contract uploaded'),
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->action] ?? $this->action;
    }

    public static function record(string $action, ?string $description = null, ?int $userId = null): void
    {
        $request = request();

        static::create([
            'user_id'     => $userId ?? $request->user()?->id,
            'action'      => $action,
            'description' => $description !== null ? mb_substr($description, 0, 250) : null,
            'ip_address'  => $request->ip(),
            'user_agent'  => mb_substr((string) $request->userAgent(), 0, 250),
            'created_at'  => now(),
        ]);
    }

    /** "Chrome · Android" from the user agent, for the table. */
    public function getDeviceAttribute(): ?string
    {
        $ua = (string) $this->user_agent;
        if ($ua === '') {
            return null;
        }
        $browser = match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Safari/')  => 'Safari',
            default                       => null,
        };
        $os = match (true) {
            str_contains($ua, 'Android')                                => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad')      => 'iOS',
            str_contains($ua, 'Windows')                                => 'Windows',
            str_contains($ua, 'Mac OS')                                 => 'macOS',
            str_contains($ua, 'Linux')                                  => 'Linux',
            default                                                     => null,
        };

        return collect([$browser, $os])->filter()->implode(' · ') ?: mb_substr($ua, 0, 40);
    }
}
