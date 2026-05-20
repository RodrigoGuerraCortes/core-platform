<?php

declare(strict_types=1);

use App\Core\Projects\Models\Project;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    // TenantContext is scoped — same instance persists across tests in the same
    // PHP process. Clear between tests to prevent state leakage.
    app(TenantContextContract::class)->clear();
});

// ─── Test helpers ─────────────────────────────────────────────────────────────

/**
 * Create a user and attach them to the given tenant with the specified role.
 */
function attachUserToTenant(Tenant $tenant, string $role = 'member'): User
{
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => $role]);

    return $user;
}

/**
 * Create a project directly for a tenant, bypassing TenantContext.
 * Used only in test setup. tenant_id is passed explicitly so the creating
 * event does not need to read from context.
 */
function seedProjectForTenant(Tenant $tenant, array $attrs = []): Project
{
    return Project::create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Seed Project',
        'status' => 'active',
    ], $attrs));
}

// ─── Tenant isolation ─────────────────────────────────────────────────────────

test('Tenant A sees only their own projects', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = attachUserToTenant($tenantA, 'member');

    seedProjectForTenant($tenantA, ['name' => 'Alpha Project']);
    seedProjectForTenant($tenantB, ['name' => 'Beta Project']);

    $this->actingAs($user)
        ->getJson('/projects', ['X-Tenant-Id' => $tenantA->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Project');
});

test('Tenant A cannot retrieve Tenant B project', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = attachUserToTenant($tenantA, 'member');

    $bProject = seedProjectForTenant($tenantB);

    // Route model binding resolves {project} with TenantScope active (tenant_id = A).
    // Since the project belongs to B, it returns null → 404. Existence is not revealed.
    $this->actingAs($user)
        ->getJson("/projects/{$bProject->id}", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('Tenant A cannot update Tenant B project', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = attachUserToTenant($tenantA, 'admin');

    $bProject = seedProjectForTenant($tenantB);

    $this->actingAs($user)
        ->patchJson("/projects/{$bProject->id}", ['name' => 'Hijacked'], ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('Tenant A cannot delete Tenant B project', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = attachUserToTenant($tenantA, 'owner');

    $bProject = seedProjectForTenant($tenantB);

    $this->actingAs($user)
        ->deleteJson("/projects/{$bProject->id}", [], ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

// ─── Authorization: owner ─────────────────────────────────────────────────────

test('owner can create a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'owner');

    $this->actingAs($user)
        ->postJson('/projects', ['name' => 'Owner Project'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Owner Project');
});

test('owner can update a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'owner');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->patchJson("/projects/{$project->id}", ['name' => 'Updated by Owner'], ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated by Owner');
});

test('owner can delete a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'owner');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->deleteJson("/projects/{$project->id}", [], ['X-Tenant-Id' => $tenant->id])
        ->assertNoContent();
});

// ─── Authorization: admin ─────────────────────────────────────────────────────

test('admin can create a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'admin');

    $this->actingAs($user)
        ->postJson('/projects', ['name' => 'Admin Project'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();
});

test('admin can update a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'admin');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->patchJson("/projects/{$project->id}", ['name' => 'Updated by Admin'], ['X-Tenant-Id' => $tenant->id])
        ->assertOk();
});

test('admin can delete a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'admin');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->deleteJson("/projects/{$project->id}", [], ['X-Tenant-Id' => $tenant->id])
        ->assertNoContent();
});

// ─── Authorization: member ────────────────────────────────────────────────────

test('member can list projects', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'member');
    seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->getJson('/projects', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('member can view a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'member');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->getJson("/projects/{$project->id}", ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.id', $project->id);
});

test('member cannot create a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'member');

    $this->actingAs($user)
        ->postJson('/projects', ['name' => 'Forbidden'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member cannot update a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'member');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->patchJson("/projects/{$project->id}", ['name' => 'Forbidden'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member cannot delete a project', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'member');
    $project = seedProjectForTenant($tenant);

    $this->actingAs($user)
        ->deleteJson("/projects/{$project->id}", [], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

// ─── Runtime ─────────────────────────────────────────────────────────────────

test('missing X-Tenant-Id header returns 400', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/projects')
        ->assertStatus(400);
});

test('unauthenticated request returns 401', function (): void {
    $tenant = Tenant::factory()->create();

    $this->getJson('/projects', ['X-Tenant-Id' => $tenant->id])
        ->assertUnauthorized();
});

test('project tenant_id is auto-filled from TenantContext on create', function (): void {
    $tenant = Tenant::factory()->create();
    $user = attachUserToTenant($tenant, 'owner');

    $this->actingAs($user)
        ->postJson('/projects', ['name' => 'Auto-filled'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.tenant_id', $tenant->id);
});

test('platform admin does not automatically bypass ProjectPolicy', function (): void {
    $tenant = Tenant::factory()->create();
    // Platform admin with only 'member' role — is_platform_admin does not
    // grant write access. Policy reads membership_role, not is_platform_admin.
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $tenant->users()->attach($admin, ['membership_role' => 'member']);

    $this->actingAs($admin)
        ->postJson('/projects', ['name' => 'Platform Bypass Attempt'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});
