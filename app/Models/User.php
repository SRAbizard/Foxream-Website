<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\Notifications\VerifyEmail as FilamentVerifyEmail;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'locale', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Defaults applied to every new User instance — even before save.
     * Without this, freshly mass-assigned users have `is_active = null` in memory
     * (DB default only fires on read), which made canAccessPanel reject the
     * just-registered user on the post-signup redirect.
     */
    protected $attributes = [
        'is_active' => true,
        'locale'    => 'id',
    ];

    /**
     * Defense-in-depth: auto-assign 'student' role on user creation if the user
     * has no role yet. The Registered event listener also does this, but in
     * Filament's register flow the event dispatch is flaky — this `created`
     * hook fires INSIDE Eloquent's save, so it's guaranteed to run.
     *
     * Seeded admins use `syncRoles(['superadmin'])` to overwrite the default.
     */
    protected static function booted(): void
    {
        static::created(function (self $user): void {
            try {
                if ($user->hasAnyRole(['superadmin', 'admin', 'student'])) {
                    return;
                }
                $user->assignRole('student');
            } catch (\Throwable $e) {
                // Role table not seeded yet (e.g. early migrate:fresh) — RolesAndPermissionsSeeder
                // will create roles, and the bootstrap admin gets its role explicitly.
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Treat null is_active as active. Only an explicit `false` blocks —
        // protects against the in-memory-null trap on newly-created users.
        if ($this->is_active === false) {
            return false;
        }

        // Admins can preview any panel (incl. /dashboard for student-side QA).
        if ($this->hasAnyRole(['superadmin', 'admin'])) {
            return true;
        }

        // Students can only access /dashboard.
        return $panel->getId() === 'student' && $this->hasRole('student');
    }

    /**
     * Superadmins, and admins given "manage students" (Admin → Admin
     * accounts), see the students and run their steps.
     */
    public function canManageStudents(): bool
    {
        return $this->hasRole('superadmin') || $this->can('manage students');
    }

    /** Everyone who handles students, for notifications about them. */
    public static function studentManagers(): \Illuminate\Support\Collection
    {
        return static::role(['superadmin', 'admin'])->get()->filter->canManageStudents()->values();
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: config('app.locale');
    }

    // ─── Student profile relations ──────────────────────────────────────
    public function studentProfile(): HasOne     { return $this->hasOne(StudentProfile::class); }
    public function educations(): HasMany        { return $this->hasMany(StudentEducation::class)->orderByDesc('end_year'); }
    public function workExperiences(): HasMany   { return $this->hasMany(StudentWorkExperience::class)->orderByDesc('start_date'); }
    public function familyMembers(): HasMany     { return $this->hasMany(StudentFamilyMember::class); }
    public function languages(): HasMany         { return $this->hasMany(StudentLanguage::class); }
    public function studentDocuments(): HasMany  { return $this->hasMany(StudentDocument::class)->latest(); }
    public function applications(): HasMany      { return $this->hasMany(Application::class)->latest(); }

    /**
     * Convenience: get or auto-create the 1:1 profile row.
     */
    public function profile(): StudentProfile
    {
        return $this->studentProfile()->firstOrCreate([]);
    }

    /**
     * Override Laravel's default email-verification notification with the
     * Filament-flavoured one, built against the student panel's signed route
     * so the verify link routes back into /dashboard/email-verification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $panel = Filament::getPanel('student', isStrict: false);

        if (! $panel) {
            parent::sendEmailVerificationNotification();
            return;
        }

        $url = URL::temporarySignedRoute(
            $panel->generateRouteName('auth.email-verification.verify'),
            now()->addHour(),
            [
                'id'   => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ],
        );

        $notification      = new FilamentVerifyEmail;
        $notification->url = $url;

        $this->notify($notification);
    }
}
