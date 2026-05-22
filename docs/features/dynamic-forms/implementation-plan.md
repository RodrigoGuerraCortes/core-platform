# DynamicForms — Implementation Plan

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Implementation is ordered to deliver a working vertical slice as early as possible. Each phase is independently deployable. Phases build on each other — no phase skips platform conventions.

This plan does not include timeline estimates. It sequences work by dependency and risk.

---

## Phase 1 — Backend Module Scaffold

**Goal:** Empty but correct module shell. No business logic yet.

Tasks:
1. Run scaffolding command: `php artisan core:make-module DynamicForms`
2. Verify generated structure:
   - `App\Core\DynamicForms\Providers\DynamicFormsServiceProvider`
   - Directory layout per `core-module-structure.md`
3. Register `DynamicFormsServiceProvider` in `bootstrap/providers.php`
4. Write smoke test: `DynamicFormsServiceProvider` boots without exception

**Acceptance:** Module registers. No routes, no models, no migrations yet.

---

## Phase 2 — Database Migrations

**Goal:** All three tables exist, correctly indexed, correctly constrained.

Tasks:
1. `create_dynamic_forms_table` — create `dynamic_forms` without FK on `active_version_id`
2. `create_dynamic_form_versions_table` — create `dynamic_form_versions` with FK to `dynamic_forms`
3. `add_active_version_fk_to_dynamic_forms` — add circular FK
4. `create_dynamic_form_submissions_table`
5. Run migrations on local dev environment
6. Verify rollback works for all four migrations

**Acceptance:** `php artisan migrate` and `php artisan migrate:rollback` succeed cleanly.

---

## Phase 3 — Eloquent Models

**Goal:** Models with correct scopes, casts, relationships, and ULIDs.

Tasks:
1. `Form` model — `HasUlids`, `TenantScope`, casts, relationships
2. `FormVersion` model — `HasUlids`, casts (schema → array), relationships, immutability guard (no `update()`)
3. `FormSubmission` model — `HasUlids`, `TenantScope`, casts, relationships, immutability guard
4. Unit tests for tenant scope on all three models
5. Unit tests for relationships (form → versions, form → activeVersion, etc.)
6. Verify `FormVersion` and `FormSubmission` cannot be updated (test `update()` throws or is stubbed)

**Acceptance:** Models boot, relationships resolve, tenant scope filters correctly in tests.

---

## Phase 4 — Schema Validator

**Goal:** `FormSchemaValidator` validates a schema array and returns errors.

Tasks:
1. Implement `App\Core\DynamicForms\Validation\FormSchemaValidator`
2. Validate all rules from validation-strategy.md Layer 1
3. Return structured errors in dot-notation format
4. Unit tests covering:
   - Valid schema passes
   - Missing required properties fail
   - Duplicate field keys fail
   - Reserved key names fail
   - Select without options fails
   - Unknown field type fails
   - `conditional.when` references non-existent key fails

**Acceptance:** 100% of schema validation rules covered by unit tests.

---

## Phase 5 — Form CRUD (without submission)

**Goal:** Full Form + FormVersion CRUD API.

Tasks:
1. `FormPolicy` — implement all policy methods
2. `CreateFormCommand` + `CreateFormAction`
3. `UpdateFormCommand` + `UpdateFormAction`
4. `PublishFormCommand` + `PublishFormAction` (creates version + sets active_version_id)
5. `UnpublishFormCommand` + `UnpublishFormAction`
6. `ArchiveFormCommand` + `ArchiveFormAction`
7. `CreateFormVersionCommand` + `CreateFormVersionAction` (calls `FormSchemaValidator`)
8. API Resources: `FormResource`, `FormListResource`, `FormVersionResource`, `FormVersionListResource`
9. Form Request classes: `CreateFormRequest`, `UpdateFormRequest`, `CreateFormVersionRequest`
10. Routes registered via `TenantRouteRegistrar` in `DynamicFormsServiceProvider`
11. Feature tests for all endpoints (authorization, happy path, sad path)

**Acceptance:** All Form + FormVersion endpoints pass feature tests. All authorization rules enforced.

---

## Phase 6 — Submission Validator + Submission API

**Goal:** Forms can be submitted and responses stored.

Tasks:
1. Implement `App\Core\DynamicForms\Validation\FormSubmissionValidator`
2. Implement all field-type validation rules (see validation-strategy.md Layer 3)
3. `FormSubmissionPolicy` — viewAny (admin/owner/member-own), view, create, delete (false)
4. `SubmitFormCommand` + `SubmitFormAction` (validates, creates `FormSubmission`)
5. `FormSubmissionResource`
6. `SubmitFormRequest` (wraps payload field)
7. Submission endpoints: `POST /submissions`, `GET /submissions`, `GET /submissions/{id}`
8. Feature tests: happy path, required field missing, invalid value, duplicate submission guard, archived form

**Acceptance:** Full submission lifecycle passes feature tests. Payload stored matches validated input.

---

## Phase 7 — Frontend Module Scaffold

**Goal:** Empty but correctly structured frontend module.

Tasks:
1. Create `src/modules/dynamic-forms/` directory structure (per frontend-rendering.md)
2. Create `queryKeys.ts`
3. Create `index.ts` with empty exports
4. Register routes in `router/index.ts` (empty pages as placeholders)
5. Verify TypeScript compiles with no errors

**Acceptance:** Frontend builds. No type errors. Empty pages render.

---

## Phase 8 — API Composables + Query Integration

**Goal:** All TanStack Query composables for forms, versions, and submissions.

Tasks:
1. Implement all API functions in `api/forms.ts`, `api/formVersions.ts`, `api/formSubmissions.ts`
2. Implement `useFormList`, `useForm`, `useFormCreate`, `useFormUpdate`, `useFormPublish`, `useFormArchive`
3. Implement `useFormVersion`, `useFormVersionCreate`
4. Implement `useFormSubmit`, `useFormSubmissionList`
5. TypeScript types: `form.ts`, `formVersion.ts`, `formSubmission.ts`, `forms.ts`
6. Unit tests for composables (mocked API)

**Acceptance:** Composables have correct TypeScript return types. Tests pass.

---

## Phase 9 — Field Renderer Registry + DynamicFormRenderer

**Goal:** Schema renders correctly in the browser.

Tasks:
1. Implement `shared/lib/fieldRenderers.ts` registry
2. Implement `shared/types/forms.ts` TypeScript schema types
3. Implement `FieldRenderer.vue` (type resolver)
4. Implement each field renderer component (text, textarea, number, date, select, checkbox, radio, email, section, file placeholder, unknown)
5. Implement `useFormRenderer` composable (visible fields, Zod schema derivation, handleSubmit)
6. Implement `DynamicFormRenderer.vue`
7. Smoke test: render a schema with all field types in a Storybook story or test harness

**Acceptance:** All field types render. `DynamicFormRenderer` emits correct payload on submit.

---

## Phase 10 — Pages + End-to-End Flow

**Goal:** Complete user-facing pages wired to composables.

Tasks:
1. `FormListPage.vue` — list forms with status filter, loading/empty/error states
2. `FormDetailPage.vue` — show form info, version history
3. `FormBuilderPage.vue` — admin: create version with raw JSON schema editor (V1 — no visual builder)
4. `FormSubmitPage.vue` — fetch schema, render, submit, show success/error
5. `SubmissionListPage.vue` — admin: paginated submission list
6. Router guards: redirect members away from admin pages
7. End-to-end test: create form → publish → submit → view submission

**Acceptance:** Full flow works in the browser. All four async states handled on all pages.

---

## Phase 11 — Testing Completeness Audit

**Goal:** No gaps in test coverage before declaring the module complete.

Checklist:
- [ ] Schema validator: all rules tested
- [ ] Submission validator: all field types tested
- [ ] All policy methods tested
- [ ] All API endpoints have feature tests (happy + error paths)
- [ ] All composables have unit tests
- [ ] `DynamicFormRenderer` has component tests for each field type
- [ ] Tenant isolation: verify cross-tenant access is blocked in tests

**Acceptance:** All checks pass. Module is declared implementation-complete.

---

## Phase 12 — Documentation Update

**Goal:** Document what was actually built vs what was designed.

Tasks:
1. Update `known-issues.md` with anything deferred during implementation
2. Update `implementation-plan.md` status markers
3. Add to `docs/worklog/` a completion entry for DynamicForms

---

## What Is Explicitly NOT In This Plan

- Visual no-code form builder UI
- File upload implementation (`file` field type is placeholder only)
- Repeater fields
- Webhooks / notifications
- CSV/PDF export
- Public unauthenticated form access
- AI-assisted form generation
