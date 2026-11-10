<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('site.roles', []) as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $bootstrap = config('site.bootstrap_admin');

        // On the live site the first admin must come from .env: never an
        // empty or guessable login (the defaults are for a laptop only).
        if (app()->isProduction()) {
            $weak = blank($bootstrap['email']) || blank($bootstrap['password'])
                || $bootstrap['email'] === 'admin@example.com'
                || in_array($bootstrap['password'], ['password', 'secret', '12345678'], true)
                || strlen((string) $bootstrap['password']) < 12;
            if ($weak) {
                throw new \RuntimeException('Set BOOTSTRAP_ADMIN_EMAIL and a strong BOOTSTRAP_ADMIN_PASSWORD (12+ characters) in .env before seeding.');
            }
        }
        $bootstrap['email']    = $bootstrap['email'] ?: 'admin@example.com';
        $bootstrap['password'] = $bootstrap['password'] ?: 'password';
        $bootstrap['name']     = $bootstrap['name'] ?: 'Super Admin';

        $admin = User::firstOrCreate(
            ['email' => $bootstrap['email']],
            [
                'name'              => $bootstrap['name'],
                'password'          => Hash::make($bootstrap['password']),
                'locale'            => config('site.default_locale', 'id'),
                'email_verified_at' => now(),
                'is_active'         => true,
            ],
        );

        // syncRoles (not assignRole) so the bootstrap admin ends up with EXACTLY
        // 'superadmin' — the booted-hook in User::created would otherwise have
        // already assigned 'student' to this brand-new user.
        $admin->syncRoles(['superadmin']);
    }
}
