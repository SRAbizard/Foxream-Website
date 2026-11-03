<?php

namespace App\Support;

use App\Models\Application;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Accounts for trying the whole journey on a laptop: anyone with an
 * "@demo.test" email. On a local install the panels show a switcher to
 * sign in as any of them in one click, so testing student and admin
 * side by side needs no logging out and in. Never on the live site.
 */
class DemoAccounts
{
    public const DOMAIN = 'demo.test';

    public static function enabled(): bool
    {
        return app()->isLocal();
    }

    public static function isDemo(?User $user): bool
    {
        return $user !== null && Str::endsWith(Str::lower($user->email), '@'.self::DOMAIN);
    }

    /** Admins first, then students, newest first. */
    public static function users(): Collection
    {
        return User::with('roles')->where('email', 'like', '%@'.self::DOMAIN)->latest('id')->get()
            ->sortBy(fn (User $u) => $u->hasRole('student') ? 1 : 0)->values();
    }

    /** An admin, and a student whose CV is filled in so they can choose a visa target and apply. */
    public static function setUp(): array
    {
        $admin = self::account('admin@'.self::DOMAIN, 'Admin Demo', 'superadmin');
        $student = self::account('siswa@'.self::DOMAIN, 'Siswa Demo', 'student');
        self::fillCv($student);

        return [$admin, $student];
    }

    /** Fill in every box of the student's CV with sample data, so they can apply right away. */
    public static function fillCv(User $student): void
    {
        $student->update(['phone' => '+62 812-0000-0000']);

        Storage::disk('public')->put($photo = 'students/photos/demo-'.$student->id.'.png', file_get_contents(public_path('images/favicon-512.png')));
        $student->studentProfile()->updateOrCreate([], [
            'photo_path'  => $photo,
            'full_name'   => Str::upper($student->name),
            'name_kana'   => 'デモ',
            'gender'      => 'male',
            'birthdate'   => '2001-04-17',
            'religion'    => 'Islam',
            'city'        => 'Bandung',
            'province'    => 'Jawa Barat',
            'desired_job' => 'driver-truck',
            'height_cm'   => 170,
            'weight_kg'   => 65,
            'blood_type'  => 'O',
            'licenses'    => [
                ['group' => 'language', 'type' => 'jft_a2', 'year' => 2025, 'month' => 3],
                ['group' => 'other', 'type' => 'sim_a', 'year' => 2021, 'month' => 6],
            ],
            'self_pr_ja'  => '私は運転が好きで、安全運転と時間管理を大切にしています。日本で経験を積み、信頼される運転手になりたいです。',
        ]);
        $student->educations()->delete();
        $student->educations()->create(['level' => 'smk', 'institution' => 'SMK Negeri 1 Bandung', 'major' => 'Teknik Otomotif', 'start_year' => 2016, 'end_year' => 2019]);
    }

    /** Remove every demo account with its applications, documents and files. */
    public static function clear(): int
    {
        $users = User::where('email', 'like', '%@'.self::DOMAIN)->get();

        foreach ($users as $user) {
            Application::where('user_id', $user->id)->get()->each->delete();
            Storage::disk('public')->delete(array_filter([
                ...StudentDocument::where('user_id', $user->id)->pluck('file_path')->all(),
                $user->studentProfile?->photo_path,
            ]));
            $user->notifications()->delete();
            $user->delete();
        }

        return $users->count();
    }

    private static function account(string $email, string $name, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => Str::random(32)]);
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->syncRoles([$role]);

        return $user;
    }
}
