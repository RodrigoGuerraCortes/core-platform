<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Database\Seeders\Development\DevelopmentBootstrapSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Verifies that DevelopmentBootstrapSeeder creates a fully operational
 * platform state:
 *   - Acme tenant exists with slug "acme"
 *   - Admin users exist and have owner membership in Acme
 *   - All users have at least one tenant membership (no orphans)
 *   - Seeder is idempotent (safe to run twice)
 *
 * These tests guard against regression of the bootstrap gap described in:
 *   docs/adr/ADR-012-sanctum-stateful-api-middleware-ordering.md
 */
describe('DevelopmentBootstrapSeeder', function () {
    beforeEach(function () {
        // Run the seeder fresh for each test
        $this->seed(DevelopmentBootstrapSeeder::class);
    });

    it('creates the Acme tenant with the correct slug', function () {
        expect(Tenant::where('slug', 'acme')->exists())->toBeTrue();
        expect(Tenant::where('slug', 'acme')->first()->name)->toBe('Acme');
    });

    it('creates both platform-admin users', function () {
        expect(User::where('email', 'rguerracortes@gmail.com')->exists())->toBeTrue();
        expect(User::where('email', 'pabfloresrojas@gmail.com')->exists())->toBeTrue();
    });

    it('gives admin users owner membership in Acme', function () {
        $acme  = Tenant::where('slug', 'acme')->firstOrFail();
        $admin = User::where('email', 'rguerracortes@gmail.com')->firstOrFail();

        $pivot = DB::table('tenant_user')
            ->where('tenant_id', $acme->id)
            ->where('user_id', $admin->id)
            ->first();

        expect($pivot)->not->toBeNull();
        expect($pivot->membership_role)->toBe('owner');
    });

    it('leaves no user without at least one tenant membership', function () {
        $orphanCount = User::whereDoesntHave('tenants')->count();
        expect($orphanCount)->toBe(0);
    });

    it('is idempotent — running twice does not duplicate records', function () {
        // Run a second time
        $this->seed(DevelopmentBootstrapSeeder::class);

        expect(Tenant::where('slug', 'acme')->count())->toBe(1);
        expect(User::where('email', 'rguerracortes@gmail.com')->count())->toBe(1);

        $acme      = Tenant::where('slug', 'acme')->firstOrFail();
        $adminUser = User::where('email', 'rguerracortes@gmail.com')->firstOrFail();

        $membershipCount = DB::table('tenant_user')
            ->where('tenant_id', $acme->id)
            ->where('user_id', $adminUser->id)
            ->count();

        expect($membershipCount)->toBe(1);
    });
});

