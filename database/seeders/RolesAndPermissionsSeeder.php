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
