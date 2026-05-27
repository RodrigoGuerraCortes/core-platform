<?php

declare(strict_types=1);

namespace Database\Seeders\Core;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Core platform bootstrap — minimum state for the platform to be operational.
 *
 * Creates:
 *  - Platform admin users
 *  - Default development tenant (Acme)
 *  - Owner memberships
 *
 * Does NOT create vertical business data (no buildings, no forms, no tickets).
 * Idempotent: safe to re-run.
 */
class CoreSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Default development tenant ────────────────────────────────────
        $acme = Tenant::firstOrCreate(
            ['slug' => 'acme'],
            ['name' => 'Acme'],
        );

        // ── 2. Platform-admin users ──────────────────────────────────────────
        $password = env('DEFAULT_ADMIN_PASSWORD', 'ChangeMe123!');

        $admins = [
            ['name' => 'Rodrigo Guerra', 'email' => 'rguerracortes@gmail.com'],
            ['name' => 'Pablo Flores', 'email' => 'pabfloresrojas@gmail.com'],
        ];

        $userIds = [];

        foreach ($admins as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_platform_admin' => true,
                ],
            );
            $userIds[$user->id] = ['membership_role' => 'owner'];
        }

        // ── 3. Attach admin memberships ──────────────────────────────────────
        $acme->users()->syncWithoutDetaching($userIds);

        $this->command->info(
            sprintf('[CoreSeeder] Acme tenant ready (id=%d). %d admin(s) attached.', $acme->id, count($userIds)),
        );
    }
}
