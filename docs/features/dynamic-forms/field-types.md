# DynamicForms — Field Types

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

This document defines every supported field type, its schema properties, its submission payload representation, its validation rules, and its frontend renderer expectation.

Field types map directly to the `type` property in a `FieldDefinition`. Unknown types are rejected at schema creation time.

---

## Type Registry (V1)

| Type | Status | Description |
|---|---|---|
| `text` | Supported | Single-line text input |
| `textarea` | Supported | Multi-line text input |
| `number` | Supported | Numeric input |
| `date` | Supported | Date picker |
| `select` | Supported | Dropdown — single value |
| `checkbox` | Supported | Boolean toggle |
| `radio` | Supported | Single choice from list |
| `email` | Supported | Email address input |
| `file` | Placeholder | Schema only — upload not implemented |
| `section` | Supported | Visual grouping — no payload value |
| `repeater` | Deferred | Repeating field group — see known-issues.md |

---

## `text`

Single-line text input.

**Schema**

```json
{
  "key": "first_name",
  "type": "text",
  "label": "First Name",
  "placeholder": "Enter first name",
  "required": true,
  "order": 1,
  "validation": {
    "min_length": 1,
    "max_length": 100,
    "pattern": null
  }
}
```

**Submission payload value:** `string`

**Validation rules:**
- If `required`: value must be present and non-empty string
- `min_length` / `max_length`: applied if set
- `pattern`: PHP regex applied server-side if set

**Frontend renderer:** `<v-text-field>` or equivalent single-line input

---

## `textarea`

Multi-line text input.

**Schema**

```json
{
  "key": "description",
  "type": "textarea",
  "label": "Description",
  "required": false,
  "order": 2,
  "validation": {
    "min_length": null,
    "max_length": 2000
  }
}
```

**Submission payload value:** `string`

**Validation rules:** same as `text`

**Frontend renderer:** `<v-textarea>` or equivalent multi-line input

---

## `number`

Numeric input. Accepts integers or decimals.

**Schema**

```json
{
  "key": "age",
  "type": "number",
  "label": "Age",
  "required": true,
  "order": 3,
  "validation": {
    "min": 0,
    "max": 120,
    "integer_only": true
  }
}
```

**Submission payload value:** `number` (JSON numeric)

**Validation rules:**
- If `required`: value must be present and numeric
- `min` / `max`: applied if set (inclusive)
- `integer_only`: if `true`, value must have no fractional part

**Frontend renderer:** `<v-text-field type="number">` with appropriate step attribute

---

## `date`

Date input. Stores and validates as `YYYY-MM-DD` string.

**Schema**

```json
{
  "key": "birth_date",
  "type": "date",
  "label": "Date of Birth",
  "required": true,
  "order": 4,
  "validation": {
    "min_date": "1900-01-01",
    "max_date": null,
    "format": "Y-m-d"
  }
}
```

**Submission payload value:** `string` in `YYYY-MM-DD` format

**Validation rules:**
- If `required`: value must be present and a valid date string
- `min_date` / `max_date`: applied if set
- `format`: currently only `"Y-m-d"` is supported in V1

**Frontend renderer:** Date picker component or `<v-text-field type="date">`

---

## `select`

Dropdown allowing a single selection from a predefined list.

**Schema**

```json
{
  "key": "country",
  "type": "select",
  "label": "Country",
  "required": true,
  "order": 5,
  "options": [
    { "value": "us", "label": "United States" },
    { "value": "ca", "label": "Canada" }
  ],
  "validation": {
    "allow_custom_value": false
  }
}
```

**Submission payload value:** `string` matching one of `options[].value`

**Validation rules:**
- If `required`: a value must be present
- Value must be in `options[].value` unless `allow_custom_value` is `true`

**Frontend renderer:** `<v-select>` with options from schema

---

## `checkbox`

Boolean toggle. Represents yes/no, agreed/not agreed.

**Schema**

```json
{
  "key": "terms_accepted",
  "type": "checkbox",
  "label": "I accept the Terms of Service",
  "required": true,
  "order": 6,
  "validation": null
}
```

**Submission payload value:** `boolean`

**Validation rules:**
- If `required`: value must be `true` (a required checkbox must be checked)
- `false` is valid only when `required` is `false`

**Frontend renderer:** `<v-checkbox>` — bound to boolean

---

## `radio`

Single selection from a visible list of options. Semantically different from `select`: options are always visible (not collapsed in a dropdown).

**Schema**

```json
{
  "key": "preferred_contact",
  "type": "radio",
  "label": "Preferred Contact Method",
  "required": true,
  "order": 7,
  "options": [
    { "value": "email", "label": "Email" },
    { "value": "phone", "label": "Phone" },
    { "value": "sms", "label": "SMS" }
  ],
  "validation": {
    "allow_custom_value": false
  }
}
```

**Submission payload value:** `string` matching one of `options[].value`

**Validation rules:** same as `select`

**Frontend renderer:** `<v-radio-group>` with `<v-radio>` for each option

---

## `email`

Email address input. A specialized `text` type with email format validation.

**Schema**

```json
{
  "key": "contact_email",
  "type": "email",
  "label": "Contact Email",
  "required": true,
  "order": 8,
  "validation": {
    "max_length": 255
  }
}
```

**Submission payload value:** `string`

**Validation rules:**
- If `required`: value must be present and non-empty
- Value must be a valid email address format (server-side: Laravel `email` rule; client-side: Zod `.email()`)
- `max_length` applied if set

**Frontend renderer:** `<v-text-field type="email">` — browser validation + Zod validation

---

## `file`

File upload field. **Schema definition only in V1 — no upload implementation.**

The field type is recognized by the schema validator and the frontend renderer registry but:
- The backend does not process any file data in V1
- The frontend renders a disabled or placeholder input with a "coming soon" notice
- File values are not stored in `FormSubmission.payload`

**Schema**

```json
{
  "key": "resume",
  "type": "file",
  "label": "Upload Resume",
  "required": false,
  "order": 9,
  "validation": {
    "max_size_mb": 5,
    "allowed_types": ["pdf", "docx"]
  }
}
```

**V1 behavior:** The schema is accepted and stored. The field renders as a placeholder. Submissions skip file field validation entirely.

**Future implementation:** Requires integration with platform storage (S3/R2), signed upload URLs, virus scanning, and asset references in the payload.

---

## `section`

Visual grouping element. Not an input — produces no value in the submission payload.

**Schema**

```json
{
  "key": "section_personal_info",
  "type": "section",
  "label": "Personal Information",
  "help_text": "Please fill in your personal details below.",
  "required": false,
  "order": 0,
  "validation": null
}
```

**Submission payload:** This key is never present in the submission payload.

**Validation rules:** None — `required` and `validation` are ignored.

**Frontend renderer:** A visual divider with a label and optional help text. Not a form control.

---

## Reserved Keys (Cannot Be Used as Field Keys)

The following strings are reserved and may not be used as field `key` values:

```
id, tenant_id, form_id, form_version_id, submitted_by,
submitted_at, created_at, updated_at, metadata
```

---

## Adding New Field Types (Future Process)

To add a new field type:

1. Add the type string to the recognized types list in the schema validator
2. Define its schema properties and validation rules in this document
3. Implement the server-side validation logic in `FormSubmissionValidator`
4. Register a renderer component in the frontend field renderer registry
5. Add the type to the `FieldType` TypeScript union in `shared/types/forms.ts`

No field type may be added without all five steps completed. Partial additions create silent rendering failures.
