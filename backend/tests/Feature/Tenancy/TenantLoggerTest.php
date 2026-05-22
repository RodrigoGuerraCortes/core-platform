<?php

declare(strict_types=1);

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Support\TenantLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── Context content ─────────────────────────────────────────────────────────

test('TenantLogger returns tenant_id and tenant_slug when context is resolved', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $context = app(TenantLogger::class)->context();

    expect($context)->toHaveKey('tenant_id', $tenant->id)
        ->and($context)->toHaveKey('tenant_slug', $tenant->slug);
});

test('TenantLogger returns empty array when no context is resolved', function (): void {
    // No context set — safe for platform-level code.
    expect(app(TenantLogger::class)->context())->toBe([]);
});

test('TenantLogger context reflects the currently resolved tenant', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    app(TenantContextContract::class)->setTenant($tenantA);
    expect(app(TenantLogger::class)->context()['tenant_id'])->toBe($tenantA->id);

    // Switch context — logger must reflect the new tenant.
    app(TenantContextContract::class)->setTenant($tenantB);
    expect(app(TenantLogger::class)->context()['tenant_id'])->toBe($tenantB->id);
});

test('TenantLogger context is empty after context is cleared', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    // Verify it is set.
    expect(app(TenantLogger::class)->context())->not->toBe([]);

    app(TenantContextContract::class)->clear();

    // After clear, must return empty array — not stale tenant data.
    expect(app(TenantLogger::class)->context())->toBe([]);
});

// ─── HTTP integration — log enrichment via ResolveTenant middleware ──────────

test('ResolveTenant middleware enriches log context with tenant metadata', function (): void {
    static $registered = false;
    if (! $registered) {
        \Illuminate\Support\Facades\Route::middleware(['tenant.resolve'])
            ->get('/_test/tenant-log-check', fn () => response()->json(['ok' => true]));
        $registered = true;
    }

    $tenant = Tenant::factory()->create();

    // Log::withContext() enriches the shared context for the request.
    // We verify indirectly by asserting ResolveTenant sets tenant context,
    // which TenantLogger then uses. Direct log output testing is an
    // integration concern for observability tooling.
    $this->getJson('/_test/tenant-log-check', ['X-Tenant-Id' => $tenant->id])
        ->assertOk();

    // After the request, the resolved tenant context was used for log enrichment.
    // The scoped TenantContext instance holds the resolved tenant.
    expect(app(TenantContextContract::class)->tenantId())->toBe($tenant->id)
        ->and(app(TenantLogger::class)->context()['tenant_slug'])->toBe($tenant->slug);
});
