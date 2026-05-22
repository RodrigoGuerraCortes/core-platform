# DynamicForms — Domain Model

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

The domain consists of four core entities. The relationships enforce the platform's key invariant: a submission is permanently bound to the exact schema version it was filled against.

---

## Entity Map

```
Tenant
  └── Form (1:many)
        └── FormVersion (1:many, immutable)
              └── FormField (1:many, embedded in version schema)
        └── FormSubmission (1:many, references FormVersion)
```

---

## `Form`

The logical container for a form. Owns metadata and the list of versions. Does not own field definitions directly — those belong to `FormVersion`.

**Identity:** `id` (ULID)  
**Tenant-scoped:** yes  
**Soft deleted:** via `archived_at` (not Laravel's `SoftDeletes` trait — explicit column)

### Attributes

| Attribute | Type | Description |
|---|---|---|
| `id` | ULID | Primary key |
| `tenant_id` | ULID | Owning tenant |
| `name` | string(255) | Display name |
| `description` | text \| null | Optional description |
| `status` | enum | `draft`, `active`, `archived` |
| `active_version_id` | ULID \| null | FK to current published `FormVersion` |
| `archived_at` | timestamp \| null | Set when form is archived |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Status Transitions

```
draft → active    (publish: sets active_version_id)
active → draft    (unpublish: clears active_version_id, does not delete version)
active → archived (archive: sets archived_at, immutable from this point)
draft → archived  (archive without publishing)
```

Archived forms accept no new submissions. Existing submissions remain accessible.

### Invariants

- `active_version_id` must reference a `FormVersion` belonging to this `Form`
- A form cannot be unarchived once `archived_at` is set
- Deleting a form is not permitted while it has submissions — archive instead

---

## `FormVersion`

An immutable snapshot of the form schema at a point in time. Once created, a `FormVersion` record is never mutated. Editing a form creates a new version.

**Identity:** `id` (ULID)  
**Tenant-scoped:** yes (through form)  
**Immutable:** yes — no updates after creation

### Attributes

| Attribute | Type | Description |
|---|---|---|
| `id` | ULID | Primary key |
| `form_id` | ULID | Parent form |
| `tenant_id` | ULID | Denormalized from form (for query performance) |
| `version_number` | integer | Monotonic, scoped per form (1, 2, 3…) |
| `schema` | JSON | Full field definitions — see schema-contract.md |
| `label` | string \| null | Optional human label (e.g., "v2 — added phone field") |
| `published_at` | timestamp \| null | When this version became active |
| `created_at` | timestamp | |

### Invariants

- `version_number` is assigned by the application at creation — never user-supplied
- `schema` is validated at write time before the record is persisted
- A `FormVersion` cannot be deleted if referenced by any `FormSubmission`

---

## `FormField` (Embedded, not a separate table)

Form fields are defined inside the `schema` JSON column on `FormVersion`. They are not stored as rows in a separate table in V1.

This is an explicit design decision: field definitions are part of the immutable version snapshot. A separate `form_fields` table would require complex versioning joins and risks partial version corruption.

Each field in the schema JSON conforms to a typed `FormFieldDefinition` structure. See schema-contract.md.

**When a separate `form_fields` table makes sense (future):** if field-level analytics, templating, or reusable field libraries are introduced. That is out of scope for V1.

---

## `FormSubmission`

Stores one completed form submission. Captures the payload at submission time, references the exact version used, and is permanently immutable after creation.

**Identity:** `id` (ULID)  
**Tenant-scoped:** yes  
**Immutable:** yes — no updates after creation

### Attributes

| Attribute | Type | Description |
|---|---|---|
| `id` | ULID | Primary key |
| `form_id` | ULID | Parent form |
| `form_version_id` | ULID | Exact version used at time of submission |
| `tenant_id` | ULID | Owning tenant |
| `submitted_by` | ULID \| null | User ID if authenticated |
| `payload` | JSON | Field key → value map as submitted |
| `metadata` | JSON \| null | IP address, user agent, submission source |
| `submitted_at` | timestamp | Canonical submission timestamp |
| `created_at` | timestamp | |

### Invariants

- `form_version_id` must reference the form's `active_version_id` at time of submission
- `payload` is validated against the referenced version schema before the record is created
- Once created, `FormSubmission` records are never mutated
- Submissions are NOT deleted when their parent form is archived

---

## Eloquent Model Namespace

```
App\Core\DynamicForms\Models\Form
App\Core\DynamicForms\Models\FormVersion
App\Core\DynamicForms\Models\FormSubmission
```

All models:
- Use `HasUlids` trait
- Include `tenant_id` scoped via the global `TenantScope`
- Cast JSON columns (`schema`, `payload`, `metadata`) to arrays

---

## Model Relationships (Eloquent)

```php
// Form
form->versions()           // hasMany FormVersion
form->activeVersion()      // belongsTo FormVersion (active_version_id)
form->submissions()        // hasMany FormSubmission

// FormVersion
version->form()            // belongsTo Form
version->submissions()     // hasMany FormSubmission

// FormSubmission
submission->form()         // belongsTo Form
submission->version()      // belongsTo FormVersion
submission->submittedBy()  // belongsTo User (nullable)
```

---

## Aggregate Boundaries

| Aggregate | Root | Members |
|---|---|---|
| Form | `Form` | `FormVersion` (owned, created through Form) |
| Submission | `FormSubmission` | standalone aggregate, references Form + FormVersion |

`FormSubmission` is not part of the Form aggregate. It is created independently and merely references the form and version. This allows submissions to be queried, exported, and managed without traversing the form aggregate.
