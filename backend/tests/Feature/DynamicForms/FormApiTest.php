<?php

declare(strict_types=1);

use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormVersion;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── Test helpers ─────────────────────────────────────────────────────────────

function dfAttachUser(Tenant $tenant, string $role = 'member'): User
{
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => $role]);

    return $user;
}

function dfSeedForm(Tenant $tenant, array $attrs = []): Form
{
    return Form::create(array_merge([
        'tenant_id'   => $tenant->id,
        'name'        => 'Test Form',
        'status'      => 'draft',
    ], $attrs));
}

function dfSeedVersion(Form $form, array $schema = []): FormVersion
{
    $defaultSchema = [
        'version' => 1,
        'title'   => 'Test Form',
        'fields'  => [
            [
                'key'      => 'full_name',
                'type'     => 'text',
                'label'    => 'Full Name',
                'required' => true,
                'order'    => 1,
            ],
        ],
    ];

    $s = empty($schema) ? $defaultSchema : $schema;

    return FormVersion::create([
        'tenant_id'      => $form->tenant_id,
        'form_id'        => $form->id,
        'version_number' => ($form->versions()->max('version_number') ?? 0) + 1,
        'schema'         => $s,
        'schema_hash'    => hash('sha256', json_encode($s)),
        'label'          => null,
        'created_by'     => null,
    ]);
}

function dfPublishForm(Form $form): Form
{
    $version = dfSeedVersion($form);
    $version->markPublished();
    $form->publishVersion($version);

    return $form->fresh();
}

// ─── Tenant isolation ─────────────────────────────────────────────────────────

test('Tenant A cannot list Tenant B forms', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = dfAttachUser($tenantA, 'member');

    dfSeedForm($tenantA, ['name' => 'Form A']);
    dfSeedForm($tenantB, ['name' => 'Form B']);

    $this->actingAs($userA)
        ->getJson('/forms', ['X-Tenant-Id' => $tenantA->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Form A');
});

test('Tenant A cannot retrieve Tenant B form', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = dfAttachUser($tenantA, 'member');
    $formB   = dfSeedForm($tenantB);

    $this->actingAs($userA)
        ->getJson("/forms/{$formB->id}", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('Tenant A cannot update Tenant B form', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = dfAttachUser($tenantA, 'admin');
    $formB   = dfSeedForm($tenantB);

    $this->actingAs($userA)
        ->patchJson("/forms/{$formB->id}", ['name' => 'Hijacked'], ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('Tenant A cannot publish Tenant B form', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = dfAttachUser($tenantA, 'admin');
    $formB   = dfSeedForm($tenantB);

    $this->actingAs($userA)
        ->postJson("/forms/{$formB->id}/publish", [], ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

// ─── Authorization: owner / admin ────────────────────────────────────────────

test('owner can create a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'owner');

    $this->actingAs($user)
        ->postJson('/forms', ['name' => 'Owner Form'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Owner Form')
        ->assertJsonPath('data.status', 'draft');
});

test('admin can create a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'admin');

    $this->actingAs($user)
        ->postJson('/forms', ['name' => 'Admin Form'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();
});

test('owner can update a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'owner');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->patchJson("/forms/{$form->id}", ['name' => 'Updated Name'], ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');
});

test('admin can archive a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'admin');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/archive", [], ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

// ─── Authorization: member ────────────────────────────────────────────────────

test('member can list forms', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');
    dfSeedForm($tenant);

    $this->actingAs($user)
        ->getJson('/forms', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('member can view a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->getJson("/forms/{$form->id}", ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.id', $form->id);
});

test('member cannot create a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');

    $this->actingAs($user)
        ->postJson('/forms', ['name' => 'Forbidden'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member cannot update a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->patchJson("/forms/{$form->id}", ['name' => 'Nope'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member cannot publish a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/publish", [], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member cannot archive a form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/archive", [], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

// ─── Publish flow ─────────────────────────────────────────────────────────────

test('publishing a form sets status to active and active_version_id', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'admin');
    $form   = dfSeedForm($tenant);
    dfSeedVersion($form);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/publish", [], ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonFragment(['active_version_id' => $form->versions()->first()->id]);
});

test('cannot publish a form with no versions', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'admin');
    $form   = dfSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/publish", [], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable();
});

test('cannot publish an archived form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'admin');
    $form   = dfSeedForm($tenant, ['status' => 'archived']);
    dfSeedVersion($form);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/publish", [], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('cannot update an archived form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'admin');
    $form   = dfSeedForm($tenant, ['status' => 'archived']);

    $this->actingAs($user)
        ->patchJson("/forms/{$form->id}", ['name' => 'Nope'], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

// ─── Runtime ─────────────────────────────────────────────────────────────────

test('tenant_id is auto-filled on form creation', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'owner');

    $response = $this->actingAs($user)
        ->postJson('/forms', ['name' => 'Auto Tenant'], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();

    expect($response->json('data.tenant_id'))->toBe($tenant->id);
});

test('status filter on list', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = dfAttachUser($tenant, 'member');

    dfSeedForm($tenant, ['name' => 'Draft Form', 'status' => 'draft']);
    $active = dfSeedForm($tenant, ['name' => 'Active Form', 'status' => 'active']);

    $this->actingAs($user)
        ->getJson('/forms?status=active', ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);
});

test('unauthenticated request is rejected', function (): void {
    $this->getJson('/forms', ['X-Tenant-Id' => 1])
        ->assertUnauthorized();
});
