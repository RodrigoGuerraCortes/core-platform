<?php

declare(strict_types=1);

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Exceptions\TenantContextNotResolvedException;
use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Test-only model
//
// No production domain model uses BelongsToTenant yet. This class exists
// solely to validate scope and trait behavior in isolation.
// It is intentionally defined here and MUST NOT be used outside tests.
// ---------------------------------------------------------------------------
class TestTenantItem extends Model
{
    use BelongsToTenant;

    public $table = 'test_tenant_items';
    protected $fillable = ['name', 'tenant_id'];
}

// ---------------------------------------------------------------------------
// Test table setup
//
// RefreshDatabase wraps each test in a transaction (data rollback), but
// schema is preserved between tests. Schema::hasTable() prevents duplicate
// creation across the suite.
// ---------------------------------------------------------------------------
beforeEach(function (): void {
    if (! Schema::hasTable('test_tenant_items')) {
        Schema::create('test_tenant_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }
});

afterEach(function (): void {
    // TenantContext is scoped — the same instance persists across tests in the
    // same PHP process. Explicitly clear between tests to prevent state leakage.
    app(TenantContextContract::class)->clear();
});

// ─── Scoping behavior ─────────────────────────────────────────────────────────

test('tenant-owned model returns only current tenant rows', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    // Insert rows for both tenants directly (scope does not apply to inserts).
    TestTenantItem::create(['name' => 'A-item', 'tenant_id' => $tenantA->id]);
    TestTenantItem::create(['name' => 'B-item', 'tenant_id' => $tenantB->id]);

    app(TenantContextContract::class)->setTenant($tenantA);

    $results = TestTenantItem::all();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('A-item');
});

test('Tenant A cannot see Tenant B rows', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $bItem = TestTenantItem::create(['name' => 'B-secret', 'tenant_id' => $tenantB->id]);

    app(TenantContextContract::class)->setTenant($tenantA);

    expect(TestTenantItem::count())->toBe(0)
        ->and(TestTenantItem::find($bItem->id))->toBeNull();
});

test('scoped query returns correct count across tenants', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TestTenantItem::create(['name' => 'A1', 'tenant_id' => $tenantA->id]);
    TestTenantItem::create(['name' => 'A2', 'tenant_id' => $tenantA->id]);
    TestTenantItem::create(['name' => 'B1', 'tenant_id' => $tenantB->id]);

    app(TenantContextContract::class)->setTenant($tenantA);

    expect(TestTenantItem::count())->toBe(2);
});

// ─── Auto-fill on create ──────────────────────────────────────────────────────

test('tenant_id is auto-filled on create when TenantContext is resolved', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    // No tenant_id provided — BelongsToTenant auto-fills from context.
    $item = TestTenantItem::create(['name' => 'auto-fill test']);

    expect($item->tenant_id)->toBe($tenant->id);
});

test('explicit tenant_id is not overwritten by auto-fill', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    // Context is set to tenant A, but we explicitly assign tenant B.
    // The auto-fill must not override an already-set tenant_id.
    app(TenantContextContract::class)->setTenant($tenantA);

    $item = TestTenantItem::create(['name' => 'explicit', 'tenant_id' => $tenantB->id]);

    expect($item->tenant_id)->toBe($tenantB->id);
});

// ─── No-context failure ───────────────────────────────────────────────────────

test('querying tenant-owned model without context throws TenantContextNotResolvedException', function (): void {
    // No context set — afterEach clears it between tests.
    expect(fn () => TestTenantItem::all())
        ->toThrow(TenantContextNotResolvedException::class);
});

test('find() on tenant-owned model without context throws TenantContextNotResolvedException', function (): void {
    expect(fn () => TestTenantItem::find(1))
        ->toThrow(TenantContextNotResolvedException::class);
});

test('count() on tenant-owned model without context throws TenantContextNotResolvedException', function (): void {
    expect(fn () => TestTenantItem::count())
        ->toThrow(TenantContextNotResolvedException::class);
});

// ─── Explicit bypass ──────────────────────────────────────────────────────────

test('explicit scope bypass allows cross-tenant queries', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TestTenantItem::create(['name' => 'A-item', 'tenant_id' => $tenantA->id]);
    TestTenantItem::create(['name' => 'B-item', 'tenant_id' => $tenantB->id]);

    // No context set. Bypass is intentional — reserved for platform/internal operations.
    // withoutGlobalScope(TenantScope::class) is the ONLY accepted bypass pattern.
    $all = TestTenantItem::withoutGlobalScope(TenantScope::class)->get();

    expect($all)->toHaveCount(2);
});

test('bypass is specific — withoutGlobalScope only removes TenantScope', function (): void {
    $tenant = Tenant::factory()->create();

    TestTenantItem::create(['name' => 'item', 'tenant_id' => $tenant->id]);

    // Bypass only TenantScope — context not required.
    $result = TestTenantItem::withoutGlobalScope(TenantScope::class)->first();

    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('item');
});

// ─── Global identity — users are not tenant-scoped ────────────────────────────

test('users table is not tenant-scoped', function (): void {
    // User does not use BelongsToTenant. Querying without context must not throw.
    User::factory()->count(2)->create();

    expect(User::count())->toBe(2);
});

test('User::all() does not require tenant context', function (): void {
    // No context set. This must succeed because User is a global identity.
    expect(fn () => User::all())->not->toThrow(TenantContextNotResolvedException::class);
});

// ─── Platform admin does not bypass ───────────────────────────────────────────

test('platform admin does not automatically bypass tenant scope', function (): void {
    // is_platform_admin is an application flag. TenantScope does not read it.
    // A platform admin without a resolved TenantContext triggers the scope guard
    // just like any other user.
    User::factory()->create(['is_platform_admin' => true]);

    // No tenant context — scope must still throw.
    expect(fn () => TestTenantItem::all())
        ->toThrow(TenantContextNotResolvedException::class);
});

// ─── Relationship ─────────────────────────────────────────────────────────────

test('BelongsToTenant exposes tenant() relationship', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $item = TestTenantItem::create(['name' => 'relation test']);

    expect($item->tenant)->toBeInstanceOf(Tenant::class)
        ->and($item->tenant->id)->toBe($tenant->id);
});
