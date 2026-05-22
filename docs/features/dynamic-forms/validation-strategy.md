# DynamicForms — Validation Strategy

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Validation in DynamicForms operates at three distinct layers. Each layer has a different owner, a different timing, and a different scope. All three must pass for a submission to be accepted.

```
Layer 1: Schema validation    → at FormVersion creation time
Layer 2: Client validation    → at form fill time (browser, Zod)
Layer 3: Submission validation → at submission time (backend, against schema)
```

Only Layer 3 is authoritative. Layers 1 and 2 are safety nets for UX quality.

---

## Layer 1 — Schema Validation (at FormVersion creation)

**Owner:** Backend  
**When:** Before `FormVersion` record is created  
**Class:** `App\Core\DynamicForms\Validation\FormSchemaValidator`

This layer ensures the schema itself is structurally valid before it is stored. It catches schema authoring errors — not submission errors.

### Rules

```
1. `version` must equal 1
2. `title` must be a non-empty string (max 255)
3. `fields` must be an array
4. All field `key` values must be unique within the schema
5. No field key may be a reserved word
6. Every field key must match the pattern ^[a-z][a-z0-9_]*$
7. Every field must have: key, type, label, required (bool), order (int)
8. Every field `type` must be in the recognized type registry
9. `select` and `radio` fields must have a non-empty `options` array
10. Each option in `options` must have `value` and `label` (both strings)
11. Option `value` strings must be unique within their field's options list
12. `conditional.when` must reference a key that exists elsewhere in the schema
13. `conditional.operator` must be a recognized operator
14. Publishing a version requires at least one non-section field in `fields`
```

**On failure:** Return `422 Unprocessable Entity` with structured field-level errors:

```json
{
  "message": "The form schema is invalid.",
  "errors": {
    "fields.1.key": ["Field key 'id' is reserved and cannot be used."],
    "fields.3.options": ["Select fields must have at least one option."]
  }
}
```

Error paths use dot-notation to reference specific positions in the schema array.

---

## Layer 2 — Client Validation (at form fill time)

**Owner:** Frontend  
**When:** As the user fills in the form, before submitting  
**Library:** Zod (defined in schema-contract.md TypeScript types)

The frontend derives client-side Zod rules from the schema at render time. This is UX-only — the backend does not trust it.

### Schema-to-Zod Mapping

The frontend `FormSchemaValidator` utility (to be implemented) maps field definitions to Zod rules:

| Field type | Zod rule |
|---|---|
| `text` | `z.string().min(n).max(n).regex(pattern?)` |
| `textarea` | `z.string().min(n).max(n)` |
| `number` | `z.number().min(n).max(n)` (`.int()` if `integer_only`) |
| `date` | `z.string().regex(/^\d{4}-\d{2}-\d{2}$/)` |
| `select` | `z.enum([...option values])` |
| `radio` | `z.enum([...option values])` |
| `checkbox` | `z.boolean()` (`.refine(v => v === true)` if required) |
| `email` | `z.string().email().max(n)` |
| `file` | `z.any()` (placeholder — not validated client-side) |
| `section` | not included in validation schema |

For optional fields (`required: false`): wrap with `.optional().nullable()`.

For conditional fields: if the condition is not met (controlling field does not match), the field is excluded from the Zod schema entirely at evaluation time.

### Behavior

- Client validation fires on field blur and on submit attempt
- Client validation errors display inline, below the field
- Client validation does NOT prevent form submission if the error is intermittent (network flakiness)
- The canonical source of truth is always the server

---

## Layer 3 — Submission Validation (at submission time)

**Owner:** Backend  
**When:** In the submission Command/Action, before the `FormSubmission` record is created  
**Class:** `App\Core\DynamicForms\Validation\FormSubmissionValidator`

This is the authoritative layer. It validates the submitted payload against the `FormVersion` schema.

### Validation Process

```
1. Load the FormVersion (by ID, tenant-scoped)
2. Confirm the form is active and accepts submissions
3. For each field in schema.fields:
   a. If field type is `section` → skip
   b. If field has a conditional → evaluate condition against payload
      - If condition not met → skip validation for this field
   c. Check `required`: if true and key missing/null/empty → error
   d. Check type-specific rules against the submitted value
4. Check for unexpected keys in the payload (keys not in schema) → warn or strip
5. If all checks pass → proceed with submission creation
6. If any check fails → return 422 with field-level errors
```

### Error Response Format

Errors map to field `key` values:

```json
{
  "message": "The submitted data is invalid.",
  "errors": {
    "full_name": ["The Full Name field is required."],
    "country": ["The selected value is not a valid option."],
    "age": ["The Age must be at least 0."]
  }
}
```

Error messages use the field's `label` for human-readable output, not the raw `key`.

### Unexpected Payload Keys

Fields in the submitted payload that do not correspond to any field `key` in the schema:

- **V1 behavior:** Strip unknown keys silently. Do not return an error.
- **Rationale:** Allows frontend schema and backend schema to drift slightly during deploys without rejecting submissions.

### Required Field Semantics by Type

| Type | Empty/missing = invalid when required |
|---|---|
| `text`, `textarea`, `email` | `""`, `null`, missing key |
| `number` | `null`, missing key (0 is valid) |
| `date` | `null`, `""`, missing key |
| `select`, `radio` | `null`, `""`, missing key |
| `checkbox` | value must be `true` (not merely present) |
| `file` | skipped entirely in V1 |
| `section` | never validated |

---

## Multi-Submission Guard

If `settings.allow_multiple_submissions` is `false`:

- Before creating a `FormSubmission`, check if `submitted_by` (the current user) already has a submission for this form version
- If a submission exists → return `409 Conflict` with:

```json
{
  "message": "You have already submitted this form."
}
```

- If `submitted_by` is null (unauthenticated) → this guard cannot apply; submission proceeds

---

## Validation Error Code Reference

| HTTP Status | Scenario |
|---|---|
| `422 Unprocessable Entity` | Schema validation failed / submission payload invalid |
| `409 Conflict` | Duplicate submission attempted |
| `403 Forbidden` | User lacks permission to submit |
| `404 Not Found` | Form or version does not exist in this tenant |
| `410 Gone` | Form is archived and no longer accepts submissions |

---

## What Validation Does NOT Cover in V1

- Cross-field validation (field A must be less than field B)
- Custom server-side validation callbacks per field
- Async validation (uniqueness checks against other records)
- CAPTCHA / bot protection
- Rate limiting per user per form (infrastructure concern, not schema concern)

These are documented in known-issues.md as future considerations.
