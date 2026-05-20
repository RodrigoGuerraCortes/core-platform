<?php

declare(strict_types=1);

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Exceptions\TenantContextNotResolvedException;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Support\TenantCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
    Cache::flush();
});

// ─── Key format ───────────────────────────────────────────────────────────────

test('tenant cache key has correct prefix format', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    expect(app(TenantCache::class)->key('settings'))
        ->toBe("tenant:{$tenant->id}:settings");
});

test('tenant cache key() throws without context', function (): void {
    expect(fn () => app(TenantCache::class)->key('anything'))
        ->toThrow(TenantContextNotResolvedException::class);
});

// ─── Isolation between tenants ────────────────────────────────────────────────

test('tenant cache values are isolated between tenants', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $cache = app(TenantCache::class);

    app(TenantContextContract::class)->setTenant($tenantA);
    $cache->put('theme', 'dark');

    // Switch to tenant B — must not see tenant A's value.
    app(TenantContextContract::class)->setTenant($tenantB);
    expect($cache->get('theme'))->toBeNull();
});

test('tenant A cache does not overwrite tenant B cache for same key', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $cache = app(TenantCache::class);

    app(TenantContextContract::class)->setTenant($tenantA);
    $cache->put('settings', ['color' => 'blue']);

    app(TenantContextContract::class)->setTenant($tenantB);
    $cache->put('settings', ['color' => 'red']);

    // Verify each tenant sees their own value.
    app(TenantContextContract::class)->setTenant($tenantA);
    expect($cache->get('settings'))->toBe(['color' => 'blue']);

    app(TenantContextContract::class)->setTenant($tenantB);
    expect($cache->get('settings'))->toBe(['color' => 'red']);
});

// ─── Get / Put / Forget ───────────────────────────────────────────────────────

test('put and get a tenant cache value', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);
    $cache = app(TenantCache::class);

    $cache->put('report', ['total' => 100]);
    expect($cache->get('report'))->toBe(['total' => 100]);
});

test('forget removes a tenant cache value', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);
    $cache = app(TenantCache::class);

    $cache->put('report', ['total' => 100]);
    $cache->forget('report');

    expect($cache->get('report'))->toBeNull();
});

test('remember caches the result of a callback', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);
    $cache = app(TenantCache::class);

    $callCount = 0;
    $value = $cache->remember('computed', 60, function () use (&$callCount): int {
        $callCount++;

        return 42;
    });

    // Call again — should use cached value.
    $cache->remember('computed', 60, function () use (&$callCount): int {
        $callCount++;

        return 99;
    });

    expect($value)->toBe(42)
        ->and($callCount)->toBe(1); // Callback only called once.
});

// ─── Global cache is not affected ────────────────────────────────────────────

test('global cache keys are separate from tenant cache keys', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    Cache::put('global-key', 'global-value');

    // Tenant cache for 'global-key' must be distinct from the global key.
    expect(app(TenantCache::class)->get('global-key'))->toBeNull()
        ->and(Cache::get('global-key'))->toBe('global-value');
});

test('tenant cache put does not pollute global cache namespace', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    app(TenantCache::class)->put('settings', ['mode' => 'dark']);

    // Raw global cache lookup for the plain key should return null.
    expect(Cache::get('settings'))->toBeNull();
});
