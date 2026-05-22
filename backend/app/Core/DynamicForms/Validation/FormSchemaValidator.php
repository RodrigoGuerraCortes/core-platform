<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Validation;

/**
 * Validates the structure and content of a FormVersion schema before storage.
 *
 * This is a pure PHP validator — it does not depend on Laravel's validator,
 * database, or any model. It validates schema structure at write time.
 *
 * Returns an array of dot-notation errors (empty = valid).
 *
 * @see docs/features/dynamic-forms/validation-strategy.md (Layer 1)
 * @see docs/features/dynamic-forms/schema-contract.md
 */
final class FormSchemaValidator
{
    private const SUPPORTED_TYPES = [
        'text', 'textarea', 'number', 'date',
        'select', 'checkbox', 'radio', 'email',
        'file', 'section',
    ];

    private const RESERVED_KEYS = [
        'id', 'tenant_id', 'form_id', 'form_version_id',
        'submitted_by', 'submitted_at', 'created_at', 'updated_at', 'metadata',
    ];

    private const KEY_PATTERN = '/^[a-z][a-z0-9_]*$/';

    private const TYPES_REQUIRING_OPTIONS = ['select', 'radio'];

    /** @var array<string, string[]> */
    private array $errors = [];

    /**
     * Validate the schema. Returns dot-notation errors (empty = valid).
     *
     * @param  array<string, mixed> $schema
     * @return array<string, string[]>
     */
    public function validate(array $schema): array
    {
        $this->errors = [];

        $this->validateTopLevel($schema);

        if (empty($this->errors) && isset($schema['fields']) && is_array($schema['fields'])) {
            $this->validateFields($schema['fields']);
        }

        return $this->errors;
    }

    public function passes(array $schema): bool
    {
        return empty($this->validate($schema));
    }

    // ─── Top-level validation ─────────────────────────────────────────────────

    private function validateTopLevel(array $schema): void
    {
        if (! isset($schema['version']) || $schema['version'] !== 1) {
            $this->addError('version', 'Schema version must be 1.');
        }

        if (empty($schema['title']) || ! is_string($schema['title'])) {
            $this->addError('title', 'Schema title is required and must be a string.');
        } elseif (strlen($schema['title']) > 255) {
            $this->addError('title', 'Schema title must not exceed 255 characters.');
        }

        if (! isset($schema['fields']) || ! is_array($schema['fields'])) {
            $this->addError('fields', 'Schema fields must be an array.');
        }
    }

    // ─── Fields array validation ──────────────────────────────────────────────

    /** @param array<int, mixed> $fields */
    private function validateFields(array $fields): void
    {
        $seenKeys = [];

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                $this->addError("fields.{$index}", 'Each field must be an object.');
                continue;
            }

            $this->validateField($index, $field, $seenKeys);
        }
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, int>   &$seenKeys
     */
    private function validateField(int $index, array $field, array &$seenKeys): void
    {
        $prefix = "fields.{$index}";

        // key
        if (empty($field['key']) || ! is_string($field['key'])) {
            $this->addError("{$prefix}.key", 'Field key is required and must be a string.');
        } elseif (in_array($field['key'], self::RESERVED_KEYS, strict: true)) {
            $this->addError("{$prefix}.key", "Field key '{$field['key']}' is reserved and cannot be used.");
        } elseif (! preg_match(self::KEY_PATTERN, $field['key'])) {
            $this->addError("{$prefix}.key", "Field key '{$field['key']}' must start with a lowercase letter and contain only lowercase letters, numbers, and underscores.");
        } elseif (isset($seenKeys[$field['key']])) {
            $this->addError("{$prefix}.key", "Field key '{$field['key']}' is duplicated (first seen at index {$seenKeys[$field['key']]}).");
        } else {
            $seenKeys[$field['key']] = $index;
        }

        // type
        if (empty($field['type']) || ! is_string($field['type'])) {
            $this->addError("{$prefix}.type", 'Field type is required and must be a string.');
        } elseif (! in_array($field['type'], self::SUPPORTED_TYPES, strict: true)) {
            $this->addError("{$prefix}.type", "Field type '{$field['type']}' is not supported.");
        }

        // label
        if (empty($field['label']) || ! is_string($field['label'])) {
            $this->addError("{$prefix}.label", 'Field label is required and must be a string.');
        }

        // required
        if (! isset($field['required']) || ! is_bool($field['required'])) {
            $this->addError("{$prefix}.required", 'Field required must be a boolean.');
        }

        // order
        if (! isset($field['order']) || ! is_int($field['order'])) {
            $this->addError("{$prefix}.order", 'Field order is required and must be an integer.');
        }

        // options (required for select/radio)
        if (isset($field['type']) && in_array($field['type'], self::TYPES_REQUIRING_OPTIONS, strict: true)) {
            $this->validateFieldOptions($prefix, $field);
        }

        // conditional
        if (! empty($field['conditional'])) {
            $this->validateConditional("{$prefix}.conditional", $field['conditional']);
        }
    }

    /**
     * @param array<string, mixed> $field
     */
    private function validateFieldOptions(string $prefix, array $field): void
    {
        if (empty($field['options']) || ! is_array($field['options'])) {
            $this->addError("{$prefix}.options", 'Select and radio fields must have at least one option.');
            return;
        }

        $seenValues = [];

        foreach ($field['options'] as $optIndex => $option) {
            if (! is_array($option)) {
                $this->addError("{$prefix}.options.{$optIndex}", 'Each option must be an object.');
                continue;
            }

            if (empty($option['value']) || ! is_string($option['value'])) {
                $this->addError("{$prefix}.options.{$optIndex}.value", 'Option value is required and must be a string.');
            } elseif (in_array($option['value'], $seenValues, strict: true)) {
                $this->addError("{$prefix}.options.{$optIndex}.value", "Option value '{$option['value']}' is duplicated.");
            } else {
                $seenValues[] = $option['value'];
            }

            if (empty($option['label']) || ! is_string($option['label'])) {
                $this->addError("{$prefix}.options.{$optIndex}.label", 'Option label is required and must be a string.');
            }
        }
    }

    /**
     * @param mixed $conditional
     */
    private function validateConditional(string $prefix, mixed $conditional): void
    {
        if (! is_array($conditional)) {
            $this->addError($prefix, 'Conditional rule must be an object.');
            return;
        }

        if (empty($conditional['when']) || ! is_string($conditional['when'])) {
            $this->addError("{$prefix}.when", 'Conditional when must reference a field key.');
        }

        $validOperators = ['equals', 'not_equals', 'contains', 'not_empty'];
        if (empty($conditional['operator']) || ! in_array($conditional['operator'], $validOperators, strict: true)) {
            $this->addError("{$prefix}.operator", 'Conditional operator must be one of: ' . implode(', ', $validOperators) . '.');
        }
    }

    private function addError(string $key, string $message): void
    {
        $this->errors[$key][] = $message;
    }
}
