# DynamicForms — Known Issues & Deferred Features

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Living document — updated as implementation proceeds  
**Date:** 2026-05-22

---

## Purpose

This document tracks:
1. Known design risks and limitations in V1
2. Explicitly deferred features (not forgotten — intentionally scoped out)
3. Assumptions that may prove incorrect during implementation

This is not a bug tracker. It is an architecture honesty document.

---

## Deferred Features

### DF-001 — File Upload (`file` field type)

**Status:** Placeholder only  
**Scope:** Schema accepts the field type; no upload logic implemented

**What is deferred:**
- Signed upload URL generation (S3/R2)
- File size and type validation at upload time
- Virus scanning
- Storing file references in `FormSubmission.payload`
- File access authorization (can a member re-download their uploaded file?)

**Why deferred:** Storage infrastructure (asset management, signed URLs, scanning) is a platform-level concern not yet established. Implementing file upload before that foundation exists creates a non-canonical pattern.

**What to do:** When platform asset management is defined, implement the `file` field type as a proper extension following the field type addition process in `field-types.md`.

---

### DF-002 — Repeater Field

**Status:** Not implemented; not even a schema placeholder

**What is deferred:**
- Schema definition for repeater (array of sub-field definitions)
- Submission payload for repeater (array of objects)
- Validation of nested repeater payloads
- Frontend renderer for repeater groups

**Why deferred:** Repeater fields introduce recursive schema validation, nested payload validation, and complex frontend rendering state. Building this correctly requires the base layer to be stable first.

**Design note:** When implemented, repeaters should embed their sub-schema as a `fields` array within the field definition, following the same `FieldDefinition` structure. The submission payload stores repeater values as `Array<Record<string, unknown>>`.

---

### DF-003 — Visual No-Code Form Builder

**Status:** Not in scope

In V1, admin users create form schemas by submitting raw JSON to `POST /api/forms/{formId}/versions`. A JSON editor is the UI.

**What is deferred:**
- Drag-and-drop field ordering
- Field configuration sidepanel
- Live preview while building
- Field template library

**Why deferred:** Building the visual builder before the schema contract and rendering engine are proven in production would create rework. The builder must be built on top of a stable schema, not alongside it.

---

### DF-004 — Public / Unauthenticated Form Submissions

**Status:** Not in scope

All submissions require authentication in V1. `submitted_by` is always set.

**What is deferred:**
- Forms flagged as `public` bypass authentication
- `submitted_by` is null for unauthenticated submissions
- CAPTCHA / bot protection for public forms
- Rate limiting per IP for public forms

**Design note:** The `submitted_by` column is already nullable in anticipation of this feature. No schema or database changes will be required when this is implemented.

---

### DF-005 — Webhooks and Notifications on Submission

**Status:** Not in scope

**What is deferred:**
- Webhook delivery to tenant-configured URLs when a submission is received
- Email confirmation to the submitter
- Email notification to the form owner
- Retry logic for failed webhook deliveries

**Why deferred:** Requires platform async infrastructure (job queues for webhook delivery, retry with exponential backoff, dead letter queues). The submission flow is synchronous in V1. Async operations are dispatched after V1 is stable.

---

### DF-006 — Submission Export (CSV / PDF)

**Status:** Not in scope

**What is deferred:**
- `GET /api/forms/{formId}/submissions/export`
- CSV export of all submission payloads
- PDF rendering of individual submissions

---

### DF-007 — Cross-Field Validation

**Status:** Not supported in V1

The submission validator validates each field independently. It cannot enforce rules like "end date must be after start date" or "total must equal sum of sub-fields."

**What is deferred:**
- Cross-field validation rules in the schema
- Custom validation callbacks
- Async uniqueness checks

**Risk:** If tenants build forms that require cross-field validation, they will get no server-side enforcement. Client-side validation can partially mitigate this via the `conditional` mechanism.

---

### DF-008 — Submission Idempotency Keys

**Status:** Not implemented

**Problem:** If a user submits a form and the network request is retried (browser retry, mobile app retry), a duplicate `FormSubmission` record may be created for forms that allow multiple submissions.

**Mitigation in V1:** Frontend disables the submit button after first click (per async-ui-conventions.md). This is UX mitigation, not a technical guarantee.

**Deferred solution:** Accept an optional `Idempotency-Key` header on the submission endpoint. If a submission with the same key already exists for this user + form, return the existing submission instead of creating a new one.

---

### DF-009 — Form-Level Access Control

**Status:** Not in scope

In V1, authorization is role-based (owner/admin/member). There is no per-form access control.

**What is deferred:**
- Restricting submission access to invited users only
- Granting a specific user `submit` access to a specific form
- Per-form viewer roles

---

### DF-010 — Submission Deletion / GDPR Purge

**Status:** Not in scope

Submissions cannot be deleted in V1. There is no deletion API for any role.

**Risk:** GDPR "right to erasure" requests cannot be honored for submission data in V1.

**Deferred solution:** A platform-level data purge mechanism (per-user, per-tenant) that cascades through all modules including DynamicForms. This must be designed at the platform level, not per-module.

---

## Known Design Risks

### DR-001 — `version_number` Race Condition

**Risk:** Two concurrent requests to create a `FormVersion` for the same form may produce the same `version_number` if the `SELECT MAX(...) + 1` logic is not serialized.

**Mitigation:** The `UNIQUE KEY uq_form_version_number (form_id, version_number)` database constraint rejects the second insert and returns a unique constraint violation. The application must catch this and retry with the correct next version number.

**Implementation note:** Use a DB-level advisory lock or a serializable transaction for version number assignment.

---

### DR-002 — Schema Size Growth

**Risk:** Schemas with many fields and many options (e.g., country dropdowns with 250 options) may make the `schema` JSON column large. This affects:
- Memory usage during validation
- Network payload size when returning the full schema
- JSON parsing overhead

**Mitigation (V1):** Set a schema size soft limit (e.g., max 100 fields, max 200 options per field) validated in `FormSchemaValidator`. Hard size limits (e.g., max 64KB JSON column) can be added as a DB constraint if needed.

---

### DR-003 — Submission Payload Schema Drift

**Risk:** If a `FormVersion` schema is retroactively misread (e.g., the `FormSchemaValidator` is updated with stricter rules), stored schemas may no longer pass current validation if re-validated.

**Mitigation:** Stored schemas are never re-validated. The `FormSchemaValidator` runs only at write time. The `FormSubmissionValidator` reads the schema as-is from the database — it does not re-run schema validation before reading it.

**Principle:** Once stored, a `FormVersion.schema` is the ground truth for all submissions that reference it. No retroactive revalidation.

---

### DR-004 — Deleted Users in `submitted_by`

**Risk:** If a user's account is deleted, `FormSubmission.submitted_by` references a non-existent user. There is no FK constraint on this column (intentional — see database-design.md).

**Mitigation:** `FormSubmissionResource` must handle `submitted_by` resolution gracefully — if the user does not exist, return `submitted_by: null` rather than throwing a model not found exception.

---

### DR-005 — Frontend Schema Type Safety

**Risk:** The TypeScript `FormSchema` type in `shared/types/forms.ts` may drift from the actual JSON stored in the database if the backend schema contract evolves without a corresponding frontend update.

**Mitigation:** The `schema.version` field (currently `1`) is the version guard. When the schema contract changes, increment to `2`. The frontend checks `schema.version` before rendering and shows a graceful error for unrecognized versions.

---

## Assumptions

| Assumption | Risk if wrong |
|---|---|
| Tenant roles (owner/admin/member) exist and are resolvable via `IdentityAuth` | Authorization policies cannot be implemented until this is confirmed |
| The platform's `TenantScope` is applied as a global scope on all models | Cross-tenant data leaks if scope is not applied |
| `ULID` generation is consistent between backend and frontend | ULID format mismatch in URLs and API responses |
| Vuetify is available for all field renderers | All field components must be re-evaluated if Vuetify is replaced |
| TanStack Vue Query v5 is used | Query API syntax differences between v4 and v5 are significant |
