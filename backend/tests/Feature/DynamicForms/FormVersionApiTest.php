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

// ─── Helpers (local to this file) ─────────────────────────────────────────────

function fvAttachUser(Tenant $tenant, string $role = 'member'): User
{
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => $role]);

    return $user;
}

function fvSeedForm(Tenant $tenant, array $attrs = []): Form
{
    return Form::create(array_merge([
        'tenant_id' => $tenant->id,
        'name'      => 'Version Test Form',
        'status'    => 'draft',
    ], $attrs));
}

/** @return array<string, mixed> */
function validSchema(string $title = 'My Form'): array
{
    return [
        'version' => 1,
        'title'   => $title,
        'fields'  => [
            [
                'key'      => 'email',
                'type'     => 'email',
                'label'    => 'Email Address',
                'required' => true,
                'order'    => 1,
            ],
        ],
    ];
}

// ─── Tenant isolation ─────────────────────────────────────────────────────────

test('Tenant A cannot list Tenant B form versions', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = fvAttachUser($tenantA, 'member');

    $formA = fvSeedForm($tenantA);
    $formB = fvSeedForm($tenantB);

    // Seed a version for both
    FormVersion::create([
        'tenant_id' => $tenantA->id, 'form_id' => $formA->id,
        'version_number' => 1, 'schema' => validSchema(), 'schema_hash' => 'abc',
    ]);
    FormVersion::create([
        'tenant_id' => $tenantB->id, 'form_id' => $formB->id,
        'version_number' => 1, 'schema' => validSchema(), 'schema_hash' => 'def',
    ]);

    // Access via Tenant B's form ID — TenantScope prevents resolution
    $this->actingAs($userA)
        ->getJson("/api/forms/{$formB->id}/versions", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('Tenant A cannot view Tenant B form version directly', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = fvAttachUser($tenantA, 'member');

    $formB    = fvSeedForm($tenantB);
    $versionB = FormVersion::create([
        'tenant_id' => $tenantB->id, 'form_id' => $formB->id,
        'version_number' => 1, 'schema' => validSchema(), 'schema_hash' => 'xyz',
    ]);

    // FormVersion has no BelongsToTenant, but the route guard resolves Form first.
    // Without TenantScope on the version, this test ensures the parent form 404s.
    $this->actingAs($userA)
        ->getJson("/api/form-versions/{$versionB->id}", ['X-Tenant-Id' => $tenantA->id])
        ->assertForbidden(); // version resolves but policy denies cross-tenant
});

// ─── Authorization ────────────────────────────────────────────────────────────

test('admin can create a form version', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", [
            'schema' => validSchema(),
            'label'  => 'v1',
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1)
        ->assertJsonPath('data.label', 'v1');
});

test('member cannot create a form version', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'member');
    $form   = fvSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", [
            'schema' => validSchema(),
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member can view form versions', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'member');
    $form   = fvSeedForm($tenant);

    FormVersion::create([
        'tenant_id'      => $tenant->id,
        'form_id'        => $form->id,
        'version_number' => 1,
        'schema'         => validSchema(),
        'schema_hash'    => hash('sha256', json_encode(validSchema())),
    ]);

    $this->actingAs($user)
        ->getJson("/api/forms/{$form->id}/versions", ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

// ─── Versioning invariants ────────────────────────────────────────────────────

test('version numbers increment monotonically', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => validSchema('V1')], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => validSchema('V2')], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 2);

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => validSchema('V3')], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 3);
});

test('published version cannot be mutated', function (): void {
    $tenant  = Tenant::factory()->create();
    $form    = fvSeedForm($tenant);
    $schema  = validSchema();
    $version = FormVersion::create([
        'tenant_id'      => $tenant->id,
        'form_id'        => $form->id,
        'version_number' => 1,
        'schema'         => $schema,
        'schema_hash'    => hash('sha256', json_encode($schema)),
    ]);

    // Attempt direct Eloquent update — the immutability guard must prevent it
    $version->update(['schema' => ['version' => 1, 'title' => 'Mutated', 'fields' => []]]);

    // Reload from DB — schema must be unchanged
    $fresh = FormVersion::find($version->id);
    expect($fresh->schema['title'])->toBe('My Form');
});

test('schema_hash is computed and stored correctly', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);
    $schema = validSchema();

    $response = $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => $schema], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();

    expect($response->json('data.schema_hash'))
        ->toBe(hash('sha256', json_encode($schema)));
});

test('cannot create version for archived form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant, ['status' => 'archived']);

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => validSchema()], ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

// ─── Schema validation at write time ─────────────────────────────────────────

test('invalid schema is rejected when creating version', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);

    // Missing required field properties
    $badSchema = [
        'version' => 1,
        'title'   => 'Bad Schema',
        'fields'  => [
            ['key' => 'id', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
        ],
    ];

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => $badSchema], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors']);
});

test('duplicate field keys are rejected in schema', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);

    $badSchema = [
        'version' => 1,
        'title'   => 'Dupe',
        'fields'  => [
            ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
            ['key' => 'name', 'type' => 'text', 'label' => 'Name Again', 'required' => false, 'order' => 2],
        ],
    ];

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => $badSchema], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable();
});

test('select field without options is rejected', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);

    $badSchema = [
        'version' => 1,
        'title'   => 'No Options',
        'fields'  => [
            ['key' => 'choice', 'type' => 'select', 'label' => 'Choice', 'required' => true, 'order' => 1],
        ],
    ];

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => $badSchema], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable();
});

test('unknown field type is rejected', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fvAttachUser($tenant, 'admin');
    $form   = fvSeedForm($tenant);

    $badSchema = [
        'version' => 1,
        'title'   => 'Unknown Type',
        'fields'  => [
            ['key' => 'fancy', 'type' => 'magic_input', 'label' => 'Fancy', 'required' => false, 'order' => 1],
        ],
    ];

    $this->actingAs($user)
        ->postJson("/api/forms/{$form->id}/versions", ['schema' => $badSchema], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable();
});
