<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development bootstrap seeder.
 *
 * Creates the canonical local-development platform state:
 *   - "Acme" tenant  (slug: acme)
 *   - Two platform-admin users with owner membership in Acme
 *
 * IDEMPOTENT: safe to re-run. Uses firstOrCreate / syncWithoutDetaching.
 * NEVER run in production (DatabaseSeeder gates this on App::environment).
 *
 * After running this seeder the following routes are operational:
 *   /t/acme/dashboard
 *   /t/acme/forms
 *   POST /api/auth/login  (with seeded credentials)
 */
class DevelopmentBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Default development tenant ────────────────────────────────────
        $acme = Tenant::firstOrCreate(
            ['slug' => 'acme'],
            ['name' => 'Acme'],
        );

        // ── 2. Platform-admin users ───────────────────────────────────────────
        $password = env('DEFAULT_ADMIN_PASSWORD', 'ChangeMe123!');

        $admins = [
            ['name' => 'Rodrigo Guerra', 'email' => 'rguerracortes@gmail.com'],
            ['name' => 'Pablo Flores',   'email' => 'pabfloresrojas@gmail.com'],
        ];

        $userIds = [];

        foreach ($admins as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_platform_admin' => true,
                ],
            );

            $userIds[$user->id] = ['membership_role' => 'owner'];
        }

        // ── 3. Attach admin memberships (idempotent) ─────────────────────────
        $acme->users()->syncWithoutDetaching($userIds);

        // ── 4. Ensure every other existing user has at least member access ────
        //       (handles legacy/test users from old seeders or manual DB ops)
        $otherIds = User::whereNotIn('id', array_keys($userIds))->pluck('id');
        $otherMap = $otherIds->mapWithKeys(fn ($id) => [$id => ['membership_role' => 'member']])->all();
        if (!empty($otherMap)) {
            $acme->users()->syncWithoutDetaching($otherMap);
        }

        $this->command->info(
            sprintf(
                '[DevelopmentBootstrapSeeder] Acme tenant ready (id=%d). Attached %d admin(s) as owner, %d other(s) as member.',
                $acme->id,
                count($userIds),
                count($otherMap),
            ),
        );
    }
}
