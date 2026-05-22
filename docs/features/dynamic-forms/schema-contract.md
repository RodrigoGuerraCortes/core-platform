# DynamicForms — Schema Contract

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

The schema is the canonical description of a form's fields, structure, and validation rules. It is stored as a JSON document in `dynamic_form_versions.schema`. Both the backend (validation) and the frontend (rendering) derive their behavior from this single source of truth.

This document defines the shape, rules, and constraints of the schema. It is the contract between backend and frontend.

---

## Top-Level Schema Structure

```json
{
  "version": 1,
  "title": "Customer Onboarding Form",
  "description": "Collect initial customer details.",
  "settings": {
    "allow_multiple_submissions": false,
    "show_progress_bar": false
  },
  "fields": [
    { ...FieldDefinition },
    { ...FieldDefinition }
  ]
}
```

| Property | Type | Required | Description |
|---|---|---|---|
| `version` | integer | yes | Schema format version (currently `1`) |
| `title` | string | yes | Display title of the form |
| `description` | string \| null | no | Optional form-level description |
| `settings` | object | no | Form-level behavior settings |
| `fields` | array | yes | Ordered list of `FieldDefinition` objects |

---

## `FieldDefinition` Structure

Every field in `fields` is a `FieldDefinition` object:

```json
{
  "key": "full_name",
  "type": "text",
  "label": "Full Name",
  "placeholder": "Enter your full name",
  "help_text": "As it appears on your ID.",
  "required": true,
  "order": 1,
  "validation": {
    "min_length": 2,
    "max_length": 100
  },
  "conditional": null
}
```

### Common Properties (all field types)

| Property | Type | Required | Description |
|---|---|---|---|
| `key` | string | yes | Unique identifier within this schema. Submission payload maps to this key |
| `type` | string | yes | Field type identifier — see field-types.md |
| `label` | string | yes | Human-readable field label |
| `placeholder` | string \| null | no | Input placeholder text |
| `help_text` | string \| null | no | Displayed below the field as a hint |
| `required` | boolean | yes | Whether the field must be present in the submission |
| `order` | integer | yes | Rendering order (1-based, ascending) |
| `validation` | object \| null | no | Type-specific validation constraints |
| `conditional` | object \| null | no | Conditional visibility rule (see below) |

### Key Rules

- `key` must be unique within the schema (no two fields may share a key)
- `key` must be a valid identifier: lowercase, underscores, no spaces — pattern: `^[a-z][a-z0-9_]*$`
- `key` must not be a reserved word: `id`, `tenant_id`, `form_id`, `submitted_at`, `submitted_by`
- Once a version is published, its field keys are permanent (they appear in submission payloads)

---

## Type-Specific Properties

Some field types add extra properties to the base definition:

### `select`, `radio`

```json
{
  "key": "country",
  "type": "select",
  "label": "Country",
  "required": true,
  "order": 3,
  "options": [
    { "value": "us", "label": "United States" },
    { "value": "ca", "label": "Canada" },
    { "value": "gb", "label": "United Kingdom" }
  ],
  "validation": null
}
```

| Property | Type | Required | Description |
|---|---|---|---|
| `options` | array | yes | List of `{ value, label }` pairs |

- `options` must have at least one entry
- `value` strings must be unique within `options`

### `section`

```json
{
  "key": "section_personal",
  "type": "section",
  "label": "Personal Information",
  "help_text": null,
  "order": 1,
  "required": false,
  "validation": null
}
```

Sections are visual grouping markers. They produce no value in the submission payload. `required` and `validation` are ignored for sections.

---

## `validation` Object

The `validation` object is type-specific. Only applicable constraints are used; unknown keys are ignored.

### Text / Textarea / Email

```json
{
  "min_length": 2,
  "max_length": 500,
  "pattern": null
}
```

| Property | Type | Description |
|---|---|---|
| `min_length` | integer \| null | Minimum character count |
| `max_length` | integer \| null | Maximum character count |
| `pattern` | string \| null | Regex pattern the value must match (server-side) |

### Number

```json
{
  "min": 0,
  "max": 100,
  "integer_only": false
}
```

### Date

```json
{
  "min_date": "2020-01-01",
  "max_date": null,
  "format": "Y-m-d"
}
```

### Checkbox

No validation object — presence in payload means `true`, absence means `false`.

### Select / Radio

```json
{
  "allow_custom_value": false
}
```

The submitted value must match one of the `options[].value` entries unless `allow_custom_value` is `true`.

---

## `conditional` Object

Conditional visibility rules control whether a field is shown/required based on another field's value.

```json
{
  "when": "employment_status",
  "operator": "equals",
  "value": "employed"
}
```

| Property | Type | Description |
|---|---|---|
| `when` | string | Key of the controlling field |
| `operator` | string | Comparison: `equals`, `not_equals`, `contains`, `not_empty` |
| `value` | scalar | Value to compare against (except for `not_empty`) |

**V1 constraint:** only simple single-condition rules. No `AND`/`OR` compound conditions. Complex conditional logic is deferred.

**Backend behavior:** if a field's condition is not met, its value in the payload is accepted but ignored — it is not validated. This prevents false validation errors for hidden fields.

---

## `settings` Object

```json
{
  "allow_multiple_submissions": false,
  "show_progress_bar": false
}
```

| Property | Type | Default | Description |
|---|---|---|---|
| `allow_multiple_submissions` | boolean | `false` | If `false`, one submission per user per form version |
| `show_progress_bar` | boolean | `false` | Render a progress indicator (frontend hint only) |

---

## Schema Validation at Write Time

When a new `FormVersion` is created, the application validates the schema before persisting:

1. `version` must equal `1`
2. `title` must be a non-empty string
3. `fields` must be an array (may be empty on draft creation, but must be non-empty before publishing)
4. Each field must have `key`, `type`, `label`, `required`, and `order`
5. All field `key` values must be unique within the schema
6. Each field `type` must be a recognized type — see field-types.md
7. Type-specific properties are validated for their type (e.g., `select` must have `options`)
8. No field may use a reserved key name

Schema validation failures return a `422 Unprocessable Entity` with a structured error response.

---

## Schema Immutability

Once a `FormVersion` record is created, its `schema` column is never updated. If the form creator needs to change fields, the application creates a new `FormVersion` record with the updated schema. The old version remains intact, preserving all submissions that reference it.

---

## TypeScript Schema Types (Frontend Contract)

The frontend consumes the schema as a TypeScript type:

```typescript
// shared/types/forms.ts (future implementation)

interface FormSchema {
  version: 1
  title: string
  description: string | null
  settings: FormSettings
  fields: FieldDefinition[]
}

interface FormSettings {
  allow_multiple_submissions: boolean
  show_progress_bar: boolean
}

interface FieldDefinition {
  key: string
  type: FieldType
  label: string
  placeholder: string | null
  help_text: string | null
  required: boolean
  order: number
  validation: Record<string, unknown> | null
  conditional: ConditionalRule | null
  options?: SelectOption[]   // only for select/radio
}

type FieldType = 'text' | 'textarea' | 'number' | 'date' | 'select'
              | 'checkbox' | 'radio' | 'email' | 'file' | 'section'

interface ConditionalRule {
  when: string
  operator: 'equals' | 'not_equals' | 'contains' | 'not_empty'
  value?: unknown
}

interface SelectOption {
  value: string
  label: string
}
```
