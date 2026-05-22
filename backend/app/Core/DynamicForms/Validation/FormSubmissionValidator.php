<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Validation;

use App\Core\DynamicForms\Models\FormVersion;

/**
 * Validates a submission payload against a FormVersion schema.
 *
 * This is Layer 3 validation — authoritative server-side validation.
 * It validates the submitted payload against the exact schema stored
 * in the referenced FormVersion. Client-side validation is UX only.
 *
 * Returns dot-notation errors keyed by field key (empty = valid).
 *
 * Supported field types: text, textarea, number, date, select, checkbox, radio, email
 * Explicitly skipped: section, file
 *
 * @see docs/features/dynamic-forms/validation-strategy.md (Layer 3)
 */
final class FormSubmissionValidator
{
    // Field types that produce no payload value — skip entirely
    private const SKIP_TYPES = ['section', 'file'];

    /** @var array<string, string[]> */
    private array $errors = [];

    /**
     * Validate $payload against $version->schema.
     *
     * Unknown keys in the payload are stripped silently (handled by caller).
     * Conditional fields: conditionals are not evaluated in V1 — all fields
     * are validated regardless of visibility state.
     *
     * @param  array<string, mixed>  $payload
     * @param  FormVersion           $version
     * @return array<string, string[]>  dot-notation field errors (empty = valid)
     */
    public function validate(array $payload, FormVersion $version): array
    {
        $this->errors = [];

        $schema = $version->schema;
        $fields = $schema['fields'] ?? [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $type = $field['type'] ?? '';

            if (in_array($type, self::SKIP_TYPES, strict: true)) {
                continue;
            }

            $key      = $field['key'] ?? '';
            $label    = $field['label'] ?? $key;
            $required = (bool) ($field['required'] ?? false);
            $value    = $payload[$key] ?? null;

            $this->validateField($key, $label, $type, $required, $value, $field);
        }

        return $this->errors;
    }

    public function passes(array $payload, FormVersion $version): bool
    {
        return empty($this->validate($payload, $version));
    }

    // ─── Per-field validation ─────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $field
     */
    private function validateField(
        string $key,
        string $label,
        string $type,
        bool $required,
        mixed $value,
        array $field,
    ): void {
        // Required check — applies to all types
        if ($required && $this->isEmpty($value, $type)) {
            $this->addError($key, "The {$label} field is required.");
            return; // Skip further type checks on missing required value
        }

        // If optional and empty, nothing more to validate
        if (! $required && $this->isEmpty($value, $type)) {
            return;
        }

        match ($type) {
            'text', 'textarea' => $this->validateText($key, $label, $value, $field),
            'email'            => $this->validateEmail($key, $label, $value, $field),
            'number'           => $this->validateNumber($key, $label, $value, $field),
            'date'             => $this->validateDate($key, $label, $value, $field),
            'select', 'radio'  => $this->validateEnum($key, $label, $value, $field),
            'checkbox'         => $this->validateCheckbox($key, $label, $value, $required),
            default            => null,
        };
    }

    private function isEmpty(mixed $value, string $type): bool
    {
        if ($type === 'checkbox') {
            // Checkbox: false is NOT empty (it's a valid value)
            return $value === null;
        }

        if ($type === 'number') {
            // 0 is a valid number — only null or absent is empty
            return $value === null;
        }

        return $value === null || $value === '';
    }

    // ─── Type-specific validators ─────────────────────────────────────────────

    /** @param array<string, mixed> $field */
    private function validateText(string $key, string $label, mixed $value, array $field): void
    {
        if (! is_string($value)) {
            $this->addError($key, "The {$label} field must be a string.");
            return;
        }

        $validation = $field['validation'] ?? [];
        $minLength  = $validation['min_length'] ?? null;
        $maxLength  = $validation['max_length'] ?? null;

        $len = mb_strlen($value);

        if ($minLength !== null && $len < (int) $minLength) {
            $this->addError($key, "The {$label} must be at least {$minLength} characters.");
        }

        if ($maxLength !== null && $len > (int) $maxLength) {
            $this->addError($key, "The {$label} must not exceed {$maxLength} characters.");
        }
    }

    /** @param array<string, mixed> $field */
    private function validateEmail(string $key, string $label, mixed $value, array $field): void
    {
        if (! is_string($value)) {
            $this->addError($key, "The {$label} field must be a string.");
            return;
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($key, "The {$label} must be a valid email address.");
        }

        $validation = $field['validation'] ?? [];
        $maxLength  = $validation['max_length'] ?? 255;

        if (mb_strlen($value) > (int) $maxLength) {
            $this->addError($key, "The {$label} must not exceed {$maxLength} characters.");
        }
    }

    /** @param array<string, mixed> $field */
    private function validateNumber(string $key, string $label, mixed $value, array $field): void
    {
        if (! is_numeric($value)) {
            $this->addError($key, "The {$label} field must be a number.");
            return;
        }

        $validation  = $field['validation'] ?? [];
        $min         = $validation['min'] ?? null;
        $max         = $validation['max'] ?? null;
        $integerOnly = (bool) ($validation['integer_only'] ?? false);

        $numeric = (float) $value;

        if ($integerOnly && floor($numeric) !== $numeric) {
            $this->addError($key, "The {$label} must be an integer.");
        }

        if ($min !== null && $numeric < (float) $min) {
            $this->addError($key, "The {$label} must be at least {$min}.");
        }

        if ($max !== null && $numeric > (float) $max) {
            $this->addError($key, "The {$label} must not be greater than {$max}.");
        }
    }

    /** @param array<string, mixed> $field */
    private function validateDate(string $key, string $label, mixed $value, array $field): void
    {
        if (! is_string($value)) {
            $this->addError($key, "The {$label} field must be a string date.");
            return;
        }

        // V1 only supports Y-m-d format
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->addError($key, "The {$label} must be a valid date in YYYY-MM-DD format.");
            return;
        }

        $validation = $field['validation'] ?? [];
        $minDate    = $validation['min_date'] ?? null;
        $maxDate    = $validation['max_date'] ?? null;

        if ($minDate !== null && $value < $minDate) {
            $this->addError($key, "The {$label} must be on or after {$minDate}.");
        }

        if ($maxDate !== null && $value > $maxDate) {
            $this->addError($key, "The {$label} must be on or before {$maxDate}.");
        }
    }

    /** @param array<string, mixed> $field */
    private function validateEnum(string $key, string $label, mixed $value, array $field): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $this->addError($key, "The {$label} field must be a string.");
            return;
        }

        $allowCustom = (bool) ($field['validation']['allow_custom_value'] ?? false);

        if ($allowCustom) {
            return;
        }

        $options       = $field['options'] ?? [];
        $allowedValues = array_column($options, 'value');

        if (! in_array((string) $value, array_map('strval', $allowedValues), strict: true)) {
            $this->addError($key, "The selected value for {$label} is not a valid option.");
        }
    }

    private function validateCheckbox(string $key, string $label, mixed $value, bool $required): void
    {
        if (! is_bool($value)) {
            $this->addError($key, "The {$label} field must be a boolean.");
            return;
        }

        if ($required && $value !== true) {
            $this->addError($key, "The {$label} field must be accepted.");
        }
    }

    private function addError(string $key, string $message): void
    {
        $this->errors[$key][] = $message;
    }
}
