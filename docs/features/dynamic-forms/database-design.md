# DynamicForms — Database Design

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Three tables. All tenant-scoped. No separate `form_fields` table in V1 — fields are embedded in the `schema` JSON column on `form_versions`. Submissions reference their version and are immutable after creation.

---

## Table: `dynamic_forms`

```sql
CREATE TABLE dynamic_forms (
    id              CHAR(26) NOT NULL,           -- ULID
    tenant_id       CHAR(26) NOT NULL,
    name            VARCHAR(255) NOT NULL,
    description     TEXT NULL,
    status          ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
    active_version_id CHAR(26) NULL,             -- FK to dynamic_form_versions
    archived_at     TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL,
    updated_at      TIMESTAMP NOT NULL,

    PRIMARY KEY (id),
    INDEX idx_dynamic_forms_tenant_id (tenant_id),
    INDEX idx_dynamic_forms_tenant_status (tenant_id, status),
    INDEX idx_dynamic_forms_active_version (active_version_id)
);
```

### Notes

- `status` drives the application state machine — not derived from `archived_at`
- `active_version_id` is nullable: `null` means the form has never been published or has been unpublished
- `archived_at` is set when `status` transitions to `archived`; once set it is never cleared
- No `deleted_at` column — soft deletes are handled by the `archived_at` + `status` combination

---

## Table: `dynamic_form_versions`

```sql
CREATE TABLE dynamic_form_versions (
    id              CHAR(26) NOT NULL,           -- ULID
    form_id         CHAR(26) NOT NULL,
    tenant_id       CHAR(26) NOT NULL,           -- denormalized for query performance
    version_number  UNSIGNED INT NOT NULL,
    schema          JSON NOT NULL,
    label           VARCHAR(255) NULL,
    published_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_form_version_number (form_id, version_number),
    INDEX idx_form_versions_form_id (form_id),
    INDEX idx_form_versions_tenant_id (tenant_id),

    CONSTRAINT fk_form_versions_form
        FOREIGN KEY (form_id) REFERENCES dynamic_forms (id),
    CONSTRAINT fk_form_versions_tenant
        FOREIGN KEY (tenant_id) REFERENCES tenants (id)
);
```

### Notes

- `version_number` is assigned atomically by the application — uses a `SELECT MAX(version_number) + 1` inside a transaction with a unique constraint as the guard
- No `updated_at` — versions are immutable after creation
- `schema` is validated by the application before insert (not by a DB constraint)
- `published_at` is set when the parent form's `active_version_id` is pointed to this version

---

## Table: `dynamic_form_submissions`

```sql
CREATE TABLE dynamic_form_submissions (
    id                  CHAR(26) NOT NULL,       -- ULID
    form_id             CHAR(26) NOT NULL,
    form_version_id     CHAR(26) NOT NULL,
    tenant_id           CHAR(26) NOT NULL,
    submitted_by        CHAR(26) NULL,           -- nullable: allows future unauthenticated submissions
    payload             JSON NOT NULL,
    metadata            JSON NULL,
    submitted_at        TIMESTAMP NOT NULL,
    created_at          TIMESTAMP NOT NULL,

    PRIMARY KEY (id),
    INDEX idx_submissions_form_id (form_id),
    INDEX idx_submissions_version_id (form_version_id),
    INDEX idx_submissions_tenant_id (tenant_id),
    INDEX idx_submissions_submitted_by (submitted_by),
    INDEX idx_submissions_submitted_at (submitted_at),

    CONSTRAINT fk_submissions_form
        FOREIGN KEY (form_id) REFERENCES dynamic_forms (id),
    CONSTRAINT fk_submissions_version
        FOREIGN KEY (form_version_id) REFERENCES dynamic_form_versions (id),
    CONSTRAINT fk_submissions_tenant
        FOREIGN KEY (tenant_id) REFERENCES tenants (id)
);
```

### Notes

- No `updated_at` — submissions are immutable after creation
- `submitted_at` is set by the application to `now()` — not a DB default — so it can be tested deterministically
- `metadata` captures: `ip_address`, `user_agent`, `source` (api, web, etc.)
- `submitted_by` references the users table but has no FK enforced at DB level — users may be deleted, submissions must remain reachable

---

## Foreign Key on `dynamic_forms.active_version_id`

The circular FK (`dynamic_forms` → `dynamic_form_versions` → `dynamic_forms`) is handled by deferring the constraint or managing it in application logic:

**Recommended approach:** Add the FK constraint as a separate migration step after both tables exist, with `ON UPDATE RESTRICT ON DELETE RESTRICT`. The application never deletes `FormVersion` records that are referenced by `active_version_id`.

```sql
ALTER TABLE dynamic_forms
    ADD CONSTRAINT fk_forms_active_version
    FOREIGN KEY (active_version_id) REFERENCES dynamic_form_versions (id)
    ON UPDATE RESTRICT ON DELETE RESTRICT;
```

---

## Migration Order

1. `create_dynamic_forms_table` — creates `dynamic_forms` without `active_version_id` FK
2. `create_dynamic_form_versions_table` — creates `dynamic_form_versions` with FK to `dynamic_forms`
3. `add_active_version_fk_to_dynamic_forms` — adds the FK from `dynamic_forms.active_version_id` to `dynamic_form_versions`
4. `create_dynamic_form_submissions_table` — creates `dynamic_form_submissions`

---

## Indexes Strategy

| Index | Rationale |
|---|---|
| `(tenant_id)` on all tables | Primary filtering dimension — every query scopes by tenant |
| `(tenant_id, status)` on forms | List forms by status within a tenant (most common query) |
| `(form_id, version_number)` unique | Enforces version monotonicity, supports version lookup |
| `(form_id)` on submissions | List all submissions for a form |
| `(submitted_at)` on submissions | Sort submissions by time |

---

## JSON Column Expectations

| Table | Column | Contains |
|---|---|---|
| `dynamic_form_versions` | `schema` | `FormSchema` — field definitions array + metadata |
| `dynamic_form_submissions` | `payload` | `Record<string, unknown>` — field key → submitted value |
| `dynamic_form_submissions` | `metadata` | `SubmissionMetadata` — ip_address, user_agent, source |

All JSON columns are read back as arrays in Eloquent via `$casts`. The application layer enforces type safety — the database does not validate JSON structure.

---

## Tenant Scope Enforcement

The global `TenantScope` Eloquent scope applies to all three models. All queries automatically include `WHERE tenant_id = ?` via the scope. There is no path to access another tenant's forms, versions, or submissions without bypassing the scope intentionally (admin-only operations).

---

## Data Retention

- Forms may be archived but not hard-deleted while they have submissions
- FormVersions may not be deleted if referenced by any submission or by `dynamic_forms.active_version_id`
- FormSubmissions are never deleted in V1 — no delete API, no cascade, no purge mechanism
- Data retention policies (GDPR purge, tenant offboarding) are out of scope for V1
