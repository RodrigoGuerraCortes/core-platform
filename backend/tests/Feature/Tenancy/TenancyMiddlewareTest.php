<?php

declare(strict_types=1);

use App\Core\Tenancy\Context\TenantContext;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Use a static flag so routes are only registered once per process.
    // Laravel's router is not reset between tests — re-registering identical
    // routes would accumulate duplicates (last definition wins, which is correct,
    // but wastes memory on long test suites).
    static $registered = false;

    if (! $registered) {
        // Standard tenant-aware route: resolve → authenticate → validate membership.
        Route::middleware(['tenant.resolve', 'auth:sanctum', 'tenant.member'])
            ->get('/_test/tenant-protected', fn () => response()->json(['ok' => true]));

        // Platform-only route: no tenant middleware — simulates admin/health routes.
        Route::get('/_test/platform-only', fn () => response()->json(['platform' => true]));

        // Misconfigured route: tenant middleware without auth preceding it.
        // Used to verify ValidateTenantMembership throws a RuntimeException
        // instead of issuing a 401 (auth is NOT tenancy's responsibility).
        Route::middleware(['tenant.resolve', 'tenant.member'])
            ->get('/_test/tenant-no-auth', fn () => response()->json(['ok' => true]));

        $registered = true;
    }
});

// ─── Contract binding ─────────────────────────────────────────────────────────

test('TenantContextContract resolves to TenantContext instance', function (): void {
    expect(app(TenantContextContract::class))->toBeInstanceOf(TenantContext::class);
});

test('TenantContextContract and TenantContext resolve to the same scoped instance', function (): void {
    $viaContract = app(TenantContextContract::class);
    $viaConcrete = app(TenantContext::class);

    expect($viaContract)->toBe($viaConcrete);
});

// ─── ResolveTenant ────────────────────────────────────────────────────────────

test('valid X-Tenant-Id initializes TenantContext', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'member']);

    $this->actingAs($user)
        ->getJson('/_test/tenant-protected', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(app(TenantContextContract::class)->tenantId())->toBe($tenant->id);
});

test('missing X-Tenant-Id returns 400 on tenant routes', function (): void {
    $this->getJson('/_test/tenant-protected')
        ->assertStatus(400);
});

test('invalid (non-existent) X-Tenant-Id returns 404', function (): void {
    $this->getJson('/_test/tenant-protected', ['X-Tenant-Id' => 999999])
        ->assertStatus(404);
});

test('soft-deleted tenant returns 404', function (): void {
    $tenant = Tenant::factory()->create();
    $tenant->delete();

    $this->getJson('/_test/tenant-protected', ['X-Tenant-Id' => $tenant->id])
        ->assertStatus(404);
});

// ─── ValidateTenantMembership ─────────────────────────────────────────────────

test('authenticated user with membership passes', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'member']);

    $this->actingAs($user)
        ->getJson('/_test/tenant-protected', ['X-Tenant-Id' => $tenant->id])
        ->assertOk();
});

test('authenticated user without membership gets 403', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/_test/tenant-protected', ['X-Tenant-Id' => $tenant->id])
        ->assertStatus(403);
});

test('user member of a different tenant gets 403 (cross-tenant isolation)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenantA->users()->attach($user, ['membership_role' => 'member']);

    // User belongs to tenant A but the request targets tenant B.
    $this->actingAs($user)
        ->getJson('/_test/tenant-protected', ['X-Tenant-Id' => $tenantB->id])
        ->assertStatus(403);
});

test('ValidateTenantMembership throws RuntimeException when no auth middleware precedes it', function (): void {
    $this->withoutExceptionHandling();

    $tenant = Tenant::factory()->create();

    expect(fn () => $this->getJson('/_test/tenant-no-auth', ['X-Tenant-Id' => $tenant->id]))
        ->toThrow(\RuntimeException::class, 'ValidateTenantMembership requires an authenticated user');
});

// ─── Membership roles ─────────────────────────────────────────────────────────

test('owner membership role is stored correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'owner']);

    $role = $tenant->users()->where('user_id', $user->id)->first()?->pivot->membership_role;

    expect($role)->toBe('owner');
});

test('admin membership role is stored correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'admin']);

    $role = $tenant->users()->where('user_id', $user->id)->first()?->pivot->membership_role;

    expect($role)->toBe('admin');
});

// ─── Platform bypass ─────────────────────────────────────────────────────────

test('platform routes bypass tenant resolution', function (): void {
    $this->getJson('/_test/platform-only')
        ->assertOk()
        ->assertJson(['platform' => true]);
});
