<?php

declare(strict_types=1);

use App\Core\DynamicForms\Models\Form;
use App\Core\DynamicForms\Models\FormSubmission;
use App\Core\DynamicForms\Models\FormVersion;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function fsAttachUser(Tenant $tenant, string $role = 'member'): User
{
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => $role]);

    return $user;
}

function fsCreatePublishedForm(Tenant $tenant, array $fields = []): Form
{
    $form = Form::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Published Form',
        'status'    => 'draft',
    ]);

    $defaultFields = [
        [
            'key'        => 'full_name',
            'type'       => 'text',
            'label'      => 'Full Name',
            'required'   => true,
            'order'      => 1,
            'validation' => ['max_length' => 100],
        ],
        [
            'key'      => 'email',
            'type'     => 'email',
            'label'    => 'Email',
            'required' => true,
            'order'    => 2,
        ],
        [
            'key'      => 'age',
            'type'     => 'number',
            'label'    => 'Age',
            'required' => false,
            'order'    => 3,
            'validation' => ['min' => 0, 'max' => 150, 'integer_only' => true],
        ],
        [
            'key'     => 'country',
            'type'    => 'select',
            'label'   => 'Country',
            'required' => true,
            'order'   => 4,
            'options' => [
                ['value' => 'us', 'label' => 'United States'],
                ['value' => 'ca', 'label' => 'Canada'],
            ],
        ],
    ];

    $schema = [
        'version'  => 1,
        'title'    => 'Published Form',
        'settings' => ['allow_multiple_submissions' => true],
        'fields'   => empty($fields) ? $defaultFields : $fields,
    ];

    $version = FormVersion::create([
        'tenant_id'      => $tenant->id,
        'form_id'        => $form->id,
        'version_number' => 1,
        'schema'         => $schema,
        'schema_hash'    => hash('sha256', json_encode($schema)),
    ]);

    $version->markPublished();
    $form->publishVersion($version);

    return $form->fresh();
}

// ─── Tenant isolation ─────────────────────────────────────────────────────────

test('Tenant A cannot submit to Tenant B form', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = fsAttachUser($tenantA, 'member');

    $formB = fsCreatePublishedForm($tenantB);

    $this->actingAs($userA)
        ->postJson("/forms/{$formB->id}/submit", [
            'payload' => ['full_name' => 'Hacker'],
        ], ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

test('Tenant A cannot list Tenant B submissions', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = fsAttachUser($tenantA, 'admin');
    $userB   = fsAttachUser($tenantB, 'member');

    $formB = fsCreatePublishedForm($tenantB);

    // Create a submission for Tenant B
    FormSubmission::create([
        'tenant_id'       => $tenantB->id,
        'form_id'         => $formB->id,
        'form_version_id' => $formB->activeVersion->id,
        'submitted_by'    => $userB->id,
        'payload'         => ['full_name' => 'B User'],
        'submitted_at'    => now(),
    ]);

    $this->actingAs($userA)
        ->getJson("/forms/{$formB->id}/submissions", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound(); // form resolves to null under Tenant A's scope
});

test('Tenant A cannot view Tenant B submission directly', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA   = fsAttachUser($tenantA, 'admin');
    $userB   = fsAttachUser($tenantB, 'member');

    $formB = fsCreatePublishedForm($tenantB);

    $submission = FormSubmission::create([
        'tenant_id'       => $tenantB->id,
        'form_id'         => $formB->id,
        'form_version_id' => $formB->activeVersion->id,
        'submitted_by'    => $userB->id,
        'payload'         => ['full_name' => 'B User', 'email' => 'b@b.com', 'country' => 'ca'],
        'submitted_at'    => now(),
    ]);

    // FormSubmission has BelongsToTenant — TenantScope filters it to 404 for a different tenant.
    $this->actingAs($userA)
        ->getJson("/submissions/{$submission->id}", ['X-Tenant-Id' => $tenantA->id])
        ->assertNotFound();
});

// ─── Happy path submission ────────────────────────────────────────────────────

test('member can submit a published form with valid payload', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => [
                'full_name' => 'Jane Smith',
                'email'     => 'jane@example.com',
                'country'   => 'ca',
            ],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.payload.full_name', 'Jane Smith')
        ->assertJsonPath('data.payload.email', 'jane@example.com')
        ->assertJsonPath('data.form_version_id', $form->activeVersion->id);
});

test('submission preserves exact form_version_id at time of submission', function (): void {
    $tenant  = Tenant::factory()->create();
    $user    = fsAttachUser($tenant, 'member');
    $form    = fsCreatePublishedForm($tenant);
    $version = $form->activeVersion;

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => ['full_name' => 'Test User', 'email' => 'test@test.com', 'country' => 'us'],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated()
        ->assertJsonPath('data.form_version_id', $version->id);
});

test('submission payload strips unknown keys silently', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $response = $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => [
                'full_name'       => 'Jane',
                'email'           => 'jane@example.com',
                'country'         => 'us',
                'unexpected_key'  => 'should be stripped',
                'another_unknown' => 123,
            ],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();

    // Unknown keys must not appear in stored payload
    expect($response->json('data.payload'))->not->toHaveKey('unexpected_key');
    expect($response->json('data.payload'))->not->toHaveKey('another_unknown');
});

test('submitted_by is set to authenticated user id', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $response = $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => ['full_name' => 'User', 'email' => 'u@u.com', 'country' => 'us'],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();

    expect($response->json('data.submitted_by'))->toBe($user->id);
});

// ─── Submission validation ────────────────────────────────────────────────────

test('missing required field returns 422 with field error', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => [
                // full_name is required but missing
                'email'   => 'jane@example.com',
                'country' => 'us',
            ],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.full_name.0', 'The Full Name field is required.');
});

test('invalid email format returns 422', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => [
                'full_name' => 'Jane',
                'email'     => 'not-an-email',
                'country'   => 'us',
            ],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['email']]);
});

test('invalid select value returns 422', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => [
                'full_name' => 'Jane',
                'email'     => 'jane@example.com',
                'country'   => 'zz', // not in options
            ],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['country']]);
});

test('number field with integer_only rejects decimal', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => [
                'full_name' => 'Jane',
                'email'     => 'jane@example.com',
                'country'   => 'us',
                'age'       => 25.5, // integer_only = true
            ],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['age']]);
});

// ─── Submission immutability ───────────────────────────────────────────────────

test('FormSubmission cannot be updated via Eloquent', function (): void {
    $tenant = Tenant::factory()->create();
    $form   = fsCreatePublishedForm($tenant);

    $submission = FormSubmission::create([
        'tenant_id'       => $tenant->id,
        'form_id'         => $form->id,
        'form_version_id' => $form->activeVersion->id,
        'payload'         => ['full_name' => 'Original'],
        'submitted_at'    => now(),
    ]);

    // Immutability guard: updating event returns false → save is cancelled
    $result = $submission->update(['payload' => ['full_name' => 'Mutated']]);
    expect($result)->toBeFalse();

    // Verify DB row is unchanged (bypass TenantScope via raw query)
    $raw = json_decode(
        \DB::table('dynamic_form_submissions')->where('id', $submission->id)->value('payload'),
        true
    );
    expect($raw['full_name'])->toBe('Original');
});

test('FormSubmission cannot be deleted via Eloquent', function (): void {
    $tenant = Tenant::factory()->create();
    $form   = fsCreatePublishedForm($tenant);

    $submission = FormSubmission::create([
        'tenant_id'       => $tenant->id,
        'form_id'         => $form->id,
        'form_version_id' => $form->activeVersion->id,
        'payload'         => ['full_name' => 'Test'],
        'submitted_at'    => now(),
    ]);

    $id = $submission->id;
    // Immutability guard: deleting event returns false → row is NOT removed
    $result = $submission->delete();
    expect($result)->toBeFalse();

    // Verify row still exists in DB (bypass TenantScope via raw query)
    expect(\DB::table('dynamic_form_submissions')->where('id', $id)->exists())->toBeTrue();
});

// ─── Form status guards ───────────────────────────────────────────────────────

test('cannot submit to a draft form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = Form::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Draft Form',
        'status'    => 'draft',
    ]);

    // Send a non-empty payload so FormRequest passes; controller short-circuits with 410
    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", ['payload' => ['dummy' => 'data']], ['X-Tenant-Id' => $tenant->id])
        ->assertStatus(410);
});

test('cannot submit to an archived form', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);
    $form->archive();

    // Send a non-empty payload so FormRequest passes; controller short-circuits with 410
    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", ['payload' => ['dummy' => 'data']], ['X-Tenant-Id' => $tenant->id])
        ->assertStatus(410);
});

// ─── Authorization for viewing submissions ───────────────────────────────────

test('admin can list all submissions', function (): void {
    $tenant  = Tenant::factory()->create();
    $admin   = fsAttachUser($tenant, 'admin');
    $member  = fsAttachUser($tenant, 'member');
    $form    = fsCreatePublishedForm($tenant);

    FormSubmission::create([
        'tenant_id' => $tenant->id, 'form_id' => $form->id,
        'form_version_id' => $form->activeVersion->id,
        'submitted_by' => $member->id, 'payload' => [], 'submitted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson("/forms/{$form->id}/submissions", ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('member cannot list all submissions', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $this->actingAs($user)
        ->getJson("/forms/{$form->id}/submissions", ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

test('member can view their own submission', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');
    $form   = fsCreatePublishedForm($tenant);

    $submission = FormSubmission::create([
        'tenant_id'       => $tenant->id,
        'form_id'         => $form->id,
        'form_version_id' => $form->activeVersion->id,
        'submitted_by'    => $user->id,
        'payload'         => ['full_name' => 'Me'],
        'submitted_at'    => now(),
    ]);

    $this->actingAs($user)
        ->getJson("/submissions/{$submission->id}", ['X-Tenant-Id' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('data.id', $submission->id);
});

test('member cannot view another member submission', function (): void {
    $tenant  = Tenant::factory()->create();
    $userA   = fsAttachUser($tenant, 'member');
    $userB   = fsAttachUser($tenant, 'member');
    $form    = fsCreatePublishedForm($tenant);

    $submissionB = FormSubmission::create([
        'tenant_id'       => $tenant->id,
        'form_id'         => $form->id,
        'form_version_id' => $form->activeVersion->id,
        'submitted_by'    => $userB->id,
        'payload'         => ['full_name' => 'User B'],
        'submitted_at'    => now(),
    ]);

    $this->actingAs($userA)
        ->getJson("/submissions/{$submissionB->id}", ['X-Tenant-Id' => $tenant->id])
        ->assertForbidden();
});

// ─── Duplicate submission guard ───────────────────────────────────────────────

test('duplicate submission is rejected when allow_multiple_submissions is false', function (): void {
    $tenant = Tenant::factory()->create();
    $user   = fsAttachUser($tenant, 'member');

    // Build a form with allow_multiple_submissions: false
    $form = Form::create([
        'tenant_id' => $tenant->id,
        'name'      => 'Single Submit Form',
        'status'    => 'draft',
    ]);

    $schema = [
        'version'  => 1,
        'title'    => 'Single Submit',
        'settings' => ['allow_multiple_submissions' => false],
        'fields'   => [
            ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
        ],
    ];

    $version = FormVersion::create([
        'tenant_id'      => $tenant->id,
        'form_id'        => $form->id,
        'version_number' => 1,
        'schema'         => $schema,
        'schema_hash'    => hash('sha256', json_encode($schema)),
    ]);

    $version->markPublished();
    $form->publishVersion($version);
    $form = $form->fresh();

    // First submission
    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => ['name' => 'First'],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertCreated();

    // Second submission — must be rejected
    $this->actingAs($user)
        ->postJson("/forms/{$form->id}/submit", [
            'payload' => ['name' => 'Second'],
        ], ['X-Tenant-Id' => $tenant->id])
        ->assertStatus(409);
});
