<?php

declare(strict_types=1);

use App\Core\DynamicForms\Models\FormVersion;
use App\Core\DynamicForms\Validation\FormSubmissionValidator;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Core\DynamicForms\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function svValidator(): FormSubmissionValidator
{
    return new FormSubmissionValidator();
}

function svMakeVersion(array $fields, array $settings = []): FormVersion
{
    $tenant = Tenant::factory()->create();
    $form   = Form::create(['tenant_id' => $tenant->id, 'name' => 'Test', 'status' => 'draft']);

    $schema = array_merge([
        'version'  => 1,
        'title'    => 'Test',
        'settings' => array_merge(['allow_multiple_submissions' => true], $settings),
        'fields'   => $fields,
    ]);

    return FormVersion::create([
        'tenant_id'      => $tenant->id,
        'form_id'        => $form->id,
        'version_number' => 1,
        'schema'         => $schema,
        'schema_hash'    => hash('sha256', json_encode($schema)),
    ]);
}

// ─── Required field validation ────────────────────────────────────────────────

test('required text field missing from payload returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate([], $version);
    expect($errors)->toHaveKey('name');
});

test('required text field empty string returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['name' => ''], $version);
    expect($errors)->toHaveKey('name');
});

test('optional text field absent passes', function (): void {
    $version = svMakeVersion([
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => false, 'order' => 1],
    ]);

    $errors = svValidator()->validate([], $version);
    expect($errors)->toBeEmpty();
});

// ─── Text / textarea validation ───────────────────────────────────────────────

test('text exceeding max_length returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'bio', 'type' => 'text', 'label' => 'Bio', 'required' => false, 'order' => 1,
            'validation' => ['max_length' => 5]],
    ]);

    $errors = svValidator()->validate(['bio' => 'This is too long'], $version);
    expect($errors)->toHaveKey('bio');
});

test('text within max_length passes', function (): void {
    $version = svMakeVersion([
        ['key' => 'bio', 'type' => 'text', 'label' => 'Bio', 'required' => false, 'order' => 1,
            'validation' => ['max_length' => 100]],
    ]);

    $errors = svValidator()->validate(['bio' => 'Short'], $version);
    expect($errors)->toBeEmpty();
});

// ─── Email validation ─────────────────────────────────────────────────────────

test('invalid email format returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['email' => 'not-an-email'], $version);
    expect($errors)->toHaveKey('email');
});

test('valid email passes', function (): void {
    $version = svMakeVersion([
        ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['email' => 'test@example.com'], $version);
    expect($errors)->toBeEmpty();
});

// ─── Number validation ────────────────────────────────────────────────────────

test('non-numeric value for number field returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'age', 'type' => 'number', 'label' => 'Age', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['age' => 'twenty'], $version);
    expect($errors)->toHaveKey('age');
});

test('number below min returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'age', 'type' => 'number', 'label' => 'Age', 'required' => true, 'order' => 1,
            'validation' => ['min' => 18]],
    ]);

    $errors = svValidator()->validate(['age' => 10], $version);
    expect($errors)->toHaveKey('age');
});

test('number above max returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'score', 'type' => 'number', 'label' => 'Score', 'required' => true, 'order' => 1,
            'validation' => ['max' => 100]],
    ]);

    $errors = svValidator()->validate(['score' => 150], $version);
    expect($errors)->toHaveKey('score');
});

test('decimal rejected when integer_only is true', function (): void {
    $version = svMakeVersion([
        ['key' => 'count', 'type' => 'number', 'label' => 'Count', 'required' => true, 'order' => 1,
            'validation' => ['integer_only' => true]],
    ]);

    $errors = svValidator()->validate(['count' => 2.5], $version);
    expect($errors)->toHaveKey('count');
});

test('zero is valid for required number', function (): void {
    $version = svMakeVersion([
        ['key' => 'count', 'type' => 'number', 'label' => 'Count', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['count' => 0], $version);
    expect($errors)->toBeEmpty();
});

// ─── Date validation ──────────────────────────────────────────────────────────

test('invalid date format returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'dob', 'type' => 'date', 'label' => 'DOB', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['dob' => '05/22/2026'], $version); // wrong format
    expect($errors)->toHaveKey('dob');
});

test('valid date in Y-m-d format passes', function (): void {
    $version = svMakeVersion([
        ['key' => 'dob', 'type' => 'date', 'label' => 'DOB', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['dob' => '2000-01-15'], $version);
    expect($errors)->toBeEmpty();
});

test('date before min_date returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'start', 'type' => 'date', 'label' => 'Start', 'required' => true, 'order' => 1,
            'validation' => ['min_date' => '2020-01-01']],
    ]);

    $errors = svValidator()->validate(['start' => '2019-12-31'], $version);
    expect($errors)->toHaveKey('start');
});

// ─── Select / radio validation ────────────────────────────────────────────────

test('select value not in options returns error', function (): void {
    $version = svMakeVersion([
        [
            'key' => 'color', 'type' => 'select', 'label' => 'Color', 'required' => true, 'order' => 1,
            'options' => [['value' => 'red', 'label' => 'Red'], ['value' => 'blue', 'label' => 'Blue']],
        ],
    ]);

    $errors = svValidator()->validate(['color' => 'green'], $version);
    expect($errors)->toHaveKey('color');
});

test('select valid option passes', function (): void {
    $version = svMakeVersion([
        [
            'key' => 'color', 'type' => 'select', 'label' => 'Color', 'required' => true, 'order' => 1,
            'options' => [['value' => 'red', 'label' => 'Red']],
        ],
    ]);

    $errors = svValidator()->validate(['color' => 'red'], $version);
    expect($errors)->toBeEmpty();
});

// ─── Checkbox validation ──────────────────────────────────────────────────────

test('required checkbox with false value returns error', function (): void {
    $version = svMakeVersion([
        ['key' => 'agreed', 'type' => 'checkbox', 'label' => 'Agree', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['agreed' => false], $version);
    expect($errors)->toHaveKey('agreed');
});

test('required checkbox with true value passes', function (): void {
    $version = svMakeVersion([
        ['key' => 'agreed', 'type' => 'checkbox', 'label' => 'Agree', 'required' => true, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['agreed' => true], $version);
    expect($errors)->toBeEmpty();
});

test('optional checkbox with false value passes', function (): void {
    $version = svMakeVersion([
        ['key' => 'newsletter', 'type' => 'checkbox', 'label' => 'Newsletter', 'required' => false, 'order' => 1],
    ]);

    $errors = svValidator()->validate(['newsletter' => false], $version);
    expect($errors)->toBeEmpty();
});

// ─── Section skipped ──────────────────────────────────────────────────────────

test('section fields are skipped during validation', function (): void {
    $version = svMakeVersion([
        ['key' => 'sec_header', 'type' => 'section', 'label' => 'Section', 'required' => false, 'order' => 0],
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
    ]);

    // Payload has name but not the section key — must pass without error for section
    $errors = svValidator()->validate(['name' => 'Alice'], $version);
    expect($errors)->toBeEmpty();
});

// ─── File field skipped ───────────────────────────────────────────────────────

test('file fields are skipped during validation in V1', function (): void {
    $version = svMakeVersion([
        ['key' => 'resume', 'type' => 'file', 'label' => 'Resume', 'required' => true, 'order' => 1],
        ['key' => 'name',   'type' => 'text', 'label' => 'Name',   'required' => true, 'order' => 2],
    ]);

    // File key absent but required — must NOT produce error (file skipped in V1)
    $errors = svValidator()->validate(['name' => 'Alice'], $version);
    expect($errors)->toBeEmpty();
});
