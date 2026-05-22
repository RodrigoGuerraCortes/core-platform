<?php

declare(strict_types=1);

use App\Core\Projects\Models\Project;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Routing\TenantRouteMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── TenantRouteMiddleware::STACK ordering ────────────────────────────────────

test('STACK has auth:sanctum as the first middleware', function (): void {
    expect(TenantRouteMiddleware::STACK[0])->toBe('auth:sanctum');
});

test('STACK has tenant.resolve before SubstituteBindings', function (): void {
    $stack = TenantRouteMiddleware::STACK;
    $resolvePos = array_search('tenant.resolve', $stack, strict: true);
    $bindingsPos = array_search(SubstituteBindings::class, $stack, strict: true);

    expect($resolvePos)->toBeLessThan($bindingsPos);
});

test('STACK has SubstituteBindings before tenant.member', function (): void {
    $stack = TenantRouteMiddleware::STACK;
    $bindingsPos = array_search(SubstituteBindings::class, $stack, strict: true);
    $memberPos = array_search('tenant.member', $stack, strict: true);

    expect($bindingsPos)->toBeLessThan($memberPos);
});

test('STACK contains all four required middleware', function (): void {
    expect(TenantRouteMiddleware::STACK)
        ->toContain('auth:sanctum')
        ->toContain('tenant.resolve')
        ->toContain(SubstituteBindings::class)
        ->toContain('tenant.member');
});

test('STACK has exactly four entries', function (): void {
    expect(TenantRouteMiddleware::STACK)->toHaveCount(4);
});

// ─── Runtime: tenant-safe route model binding ─────────────────────────────────
//
// These tests register a route using TenantRouteMiddleware::STACK and verify
// that cross-tenant entity resolution is prevented at runtime, proving the
// stack actually enforces the invariant documented in ADR-011.
// ---------------------------------------------------------------------------

beforeEach(function (): void {
    static $registered = false;

    if (! $registered) {
        \Illuminate\Support\Facades\Route::middleware(TenantRouteMiddleware::STACK)
            ->get('/_test/tenant-binding/{project}', function (Project $project) {
                return response()->json(['id' => $project->id]);
            });

        $registered = true;
    }
});

test('STACK prevents cross-tenant project resolution via route model binding', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create();
    $tenantA->users()->attach($userA, ['membership_role' => 'owner']);

    // Create project belonging to Tenant B
    $projectB = Project::create([
        'tenant_id' => $tenantB->id,
        'name' => 'Secret B Project',
        'status' => 'active',
    ]);

    // Tenant A user attempts to resolve Tenant B's project by ID
    $this->actingAs($userA)
        ->getJson("/_test/tenant-binding/{$projectB->id}", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound(); // TenantScope filters → model not found → 404
});

test('STACK allows resolution of own-tenant project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'member']);

    $project = Project::create([
        'tenant_id' => $tenant->id,
        'name' => 'My Project',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->getJson("/_test/tenant-binding/{$project->id}", ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('id', $project->id);
});
