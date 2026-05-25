<?php

declare(strict_types=1);

use App\Core\CondoFlow\Models\Building;
use App\Core\CondoFlow\Models\MaintenanceTicket;
use App\Core\CondoFlow\Models\Resident;
use App\Core\CondoFlow\Models\Unit;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function condoUser(Tenant $tenant, string $role = 'member'): User
{
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => $role]);
    return $user;
}

function seedBuilding(Tenant $tenant, array $attrs = []): Building
{
    return Building::create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Edificio Test',
        'address' => 'Calle 123',
        'floors' => 10,
    ], $attrs));
}

function seedUnit(Tenant $tenant, Building $building, array $attrs = []): Unit
{
    return Unit::create(array_merge([
        'tenant_id' => $tenant->id,
        'building_id' => $building->id,
        'number' => '101',
        'floor' => 1,
    ], $attrs));
}

function seedResident(Tenant $tenant, ?Unit $unit = null, array $attrs = []): Resident
{
    return Resident::create(array_merge([
        'tenant_id' => $tenant->id,
        'unit_id' => $unit?->id,
        'name' => 'Juan Pérez',
    ], $attrs));
}

function seedTicket(Tenant $tenant, ?Unit $unit = null, ?Resident $resident = null, array $attrs = []): MaintenanceTicket
{
    return MaintenanceTicket::create(array_merge([
        'tenant_id' => $tenant->id,
        'unit_id' => $unit?->id,
        'resident_id' => $resident?->id,
        'title' => 'Fuga de agua',
    ], $attrs));
}

// ─── Tenant Isolation ─────────────────────────────────────────────────────────

test('tenant A cannot see tenant B buildings', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = condoUser($tenantA);

    seedBuilding($tenantA, ['name' => 'Mine']);
    seedBuilding($tenantB, ['name' => 'Theirs']);

    $this->actingAs($user)
        ->getJson('/api/condoflow/buildings', ['X-Tenant-Id' => $tenantA->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mine');
});

test('tenant A cannot retrieve tenant B building', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = condoUser($tenantA);
    $b = seedBuilding($tenantB);

    $this->actingAs($user)
        ->getJson("/api/condoflow/buildings/{$b->id}", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('tenant A cannot see tenant B tickets', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = condoUser($tenantA);

    seedTicket($tenantA, null, null, ['title' => 'Mine']);
    seedTicket($tenantB, null, null, ['title' => 'Theirs']);

    $this->actingAs($user)
        ->getJson('/api/condoflow/tickets', ['X-Tenant-Id' => $tenantA->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Mine');
});

// ─── Authorization ────────────────────────────────────────────────────────────

test('owner can create a building', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'owner');

    $this->actingAs($user)
        ->postJson('/api/condoflow/buildings', ['name' => 'New Building', 'floors' => 5], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.name', 'New Building');
});

test('member cannot create a building', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');

    $this->actingAs($user)
        ->postJson('/api/condoflow/buildings', ['name' => 'Denied'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member can view buildings', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');
    seedBuilding($tenant);

    $this->actingAs($user)
        ->getJson('/api/condoflow/buildings', ['X-Tenant-Id' => $tenant->id])
        ->assertOk();
});

test('admin can update a building', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'admin');
    $building = seedBuilding($tenant);

    $this->actingAs($user)
        ->patchJson("/api/condoflow/buildings/{$building->id}", ['name' => 'Updated'], ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated');
});

test('member cannot delete a building', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');
    $building = seedBuilding($tenant);

    $this->actingAs($user)
        ->deleteJson("/api/condoflow/buildings/{$building->id}", [], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

// ─── CRUD: Units ──────────────────────────────────────────────────────────────

test('admin can create a unit', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'admin');
    $building = seedBuilding($tenant);

    $this->actingAs($user)
        ->postJson('/api/condoflow/units', [
            'building_id' => $building->id,
            'number' => '201',
            'floor' => 2,
            'type' => 'apartment',
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.number', '201');
});

test('units index supports status filter', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');
    $building = seedBuilding($tenant);
    seedUnit($tenant, $building, ['number' => 'A1', 'status' => 'available']);
    seedUnit($tenant, $building, ['number' => 'A2', 'status' => 'occupied']);

    $this->actingAs($user)
        ->getJson('/api/condoflow/units?status=available', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.number', 'A1');
});

// ─── CRUD: Residents ──────────────────────────────────────────────────────────

test('admin can create a resident', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'admin');

    $this->actingAs($user)
        ->postJson('/api/condoflow/residents', ['name' => 'María López', 'email' => 'maria@test.cl'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.name', 'María López');
});

// ─── CRUD: Tickets ────────────────────────────────────────────────────────────

test('member can create a ticket', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');

    $this->actingAs($user)
        ->postJson('/api/condoflow/tickets', ['title' => 'Broken pipe', 'priority' => 'high'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Broken pipe')
        ->assertJsonPath('data.priority', 'high');
});

test('member cannot update a ticket', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');
    $ticket = seedTicket($tenant);

    $this->actingAs($user)
        ->patchJson("/api/condoflow/tickets/{$ticket->id}", ['status' => 'resolved'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('admin can update ticket status', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'admin');
    $ticket = seedTicket($tenant);

    $this->actingAs($user)
        ->patchJson("/api/condoflow/tickets/{$ticket->id}", ['status' => 'in_progress'], ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');
});

test('tickets index supports priority filter', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');
    seedTicket($tenant, null, null, ['title' => 'High', 'priority' => 'high']);
    seedTicket($tenant, null, null, ['title' => 'Low', 'priority' => 'low']);

    $this->actingAs($user)
        ->getJson('/api/condoflow/tickets?priority=high', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'High');
});

// ─── Dashboard ────────────────────────────────────────────────────────────────

test('dashboard returns aggregate counts', function (): void {
    $tenant = Tenant::factory()->create();
    $user = condoUser($tenant, 'member');
    $building = seedBuilding($tenant);
    seedUnit($tenant, $building);
    seedResident($tenant);
    seedTicket($tenant, null, null, ['status' => 'open']);
    seedTicket($tenant, null, null, ['status' => 'in_progress']);

    $this->actingAs($user)
        ->getJson('/api/condoflow/dashboard', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.buildings_count', 1)
        ->assertJsonPath('data.units_count', 1)
        ->assertJsonPath('data.residents_count', 1)
        ->assertJsonPath('data.open_tickets_count', 1)
        ->assertJsonPath('data.in_progress_tickets_count', 1);
});

// ─── Unauthenticated ──────────────────────────────────────────────────────────

test('unauthenticated user cannot access condoflow', function (): void {
    $this->getJson('/api/condoflow/buildings')
        ->assertUnauthorized();
});
