<?php

declare(strict_types=1);

use App\Core\DynamicForms\Validation\FormSchemaValidator;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function baseSchema(array $overrides = []): array
{
    return array_merge([
        'version' => 1,
        'title'   => 'Test Form',
        'fields'  => [
            [
                'key'      => 'name',
                'type'     => 'text',
                'label'    => 'Name',
                'required' => true,
                'order'    => 1,
            ],
        ],
    ], $overrides);
}

function makeSchemaValidator(): FormSchemaValidator
{
    return new FormSchemaValidator();
}

// ─── Valid schema ──────────────────────────────────────────────────────────────

test('valid schema with all supported types passes', function (): void {
    $schema = [
        'version' => 1,
        'title'   => 'Full Schema',
        'fields'  => [
            ['key' => 'name',    'type' => 'text',     'label' => 'Name',    'required' => true,  'order' => 1],
            ['key' => 'bio',     'type' => 'textarea', 'label' => 'Bio',     'required' => false, 'order' => 2],
            ['key' => 'age',     'type' => 'number',   'label' => 'Age',     'required' => false, 'order' => 3],
            ['key' => 'dob',     'type' => 'date',     'label' => 'DOB',     'required' => false, 'order' => 4],
            ['key' => 'email',   'type' => 'email',    'label' => 'Email',   'required' => true,  'order' => 5],
            ['key' => 'agreed',  'type' => 'checkbox', 'label' => 'Agreed',  'required' => true,  'order' => 6],
            ['key' => 'sec',     'type' => 'section',  'label' => 'Section', 'required' => false, 'order' => 7],
            ['key' => 'upload',  'type' => 'file',     'label' => 'File',    'required' => false, 'order' => 8],
            [
                'key' => 'country', 'type' => 'select', 'label' => 'Country', 'required' => true, 'order' => 9,
                'options' => [['value' => 'us', 'label' => 'US']],
            ],
            [
                'key' => 'gender', 'type' => 'radio', 'label' => 'Gender', 'required' => false, 'order' => 10,
                'options' => [['value' => 'm', 'label' => 'Male'], ['value' => 'f', 'label' => 'Female']],
            ],
        ],
    ];

    expect(makeSchemaValidator()->passes($schema))->toBeTrue();
});

// ─── Top-level validation ─────────────────────────────────────────────────────

test('missing version fails', function (): void {
    $errors = makeSchemaValidator()->validate(['title' => 'T', 'fields' => []]);
    expect($errors)->toHaveKey('version');
});

test('wrong version number fails', function (): void {
    $errors = makeSchemaValidator()->validate(baseSchema(['version' => 2]));
    expect($errors)->toHaveKey('version');
});

test('missing title fails', function (): void {
    $errors = makeSchemaValidator()->validate(baseSchema(['title' => '']));
    expect($errors)->toHaveKey('title');
});

test('title over 255 chars fails', function (): void {
    $errors = makeSchemaValidator()->validate(baseSchema(['title' => str_repeat('x', 256)]));
    expect($errors)->toHaveKey('title');
});

test('fields not an array fails', function (): void {
    $errors = makeSchemaValidator()->validate(baseSchema(['fields' => 'not an array']));
    expect($errors)->toHaveKey('fields');
});

// ─── Field key validation ─────────────────────────────────────────────────────

test('reserved key id is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'id', 'type' => 'text', 'label' => 'ID', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.key');
});

test('reserved key tenant_id is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'tenant_id', 'type' => 'text', 'label' => 'T', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.key');
});

test('key with uppercase is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'MyKey', 'type' => 'text', 'label' => 'My Key', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.key');
});

test('key with spaces is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'my key', 'type' => 'text', 'label' => 'My Key', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.key');
});

test('key starting with number is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => '1name', 'type' => 'text', 'label' => 'Name', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.key');
});

test('duplicate field keys are rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 1],
        ['key' => 'name', 'type' => 'text', 'label' => 'Name Again', 'required' => false, 'order' => 2],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.1.key');
});

// ─── Field type validation ────────────────────────────────────────────────────

test('unknown field type is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'fancy', 'type' => 'magic_widget', 'label' => 'Fancy', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.type');
});

test('missing field type is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'no_type', 'label' => 'No Type', 'required' => false, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.type');
});

// ─── Select / radio options ────────────────────────────────────────────────────

test('select without options is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'color', 'type' => 'select', 'label' => 'Color', 'required' => true, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.options');
});

test('radio without options is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'opt', 'type' => 'radio', 'label' => 'Opt', 'required' => true, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.options');
});

test('select with duplicate option values is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        [
            'key' => 'color', 'type' => 'select', 'label' => 'Color', 'required' => true, 'order' => 1,
            'options' => [
                ['value' => 'red', 'label' => 'Red'],
                ['value' => 'red', 'label' => 'Red Again'],
            ],
        ],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.options.1.value');
});

test('select with valid options passes', function (): void {
    $schema = baseSchema(['fields' => [
        [
            'key' => 'color', 'type' => 'select', 'label' => 'Color', 'required' => true, 'order' => 1,
            'options' => [
                ['value' => 'red', 'label' => 'Red'],
                ['value' => 'blue', 'label' => 'Blue'],
            ],
        ],
    ]]);
    expect(makeSchemaValidator()->passes($schema))->toBeTrue();
});

// ─── Required field properties ────────────────────────────────────────────────

test('field missing label is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'name', 'type' => 'text', 'required' => true, 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.label');
});

test('field with non-bool required is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => 'yes', 'order' => 1],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.required');
});

test('field with non-int order is rejected', function (): void {
    $schema = baseSchema(['fields' => [
        ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'order' => 'first'],
    ]]);
    $errors = makeSchemaValidator()->validate($schema);
    expect($errors)->toHaveKey('fields.0.order');
});
