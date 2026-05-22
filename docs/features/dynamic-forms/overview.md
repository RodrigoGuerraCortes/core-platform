# DynamicForms — Overview

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Purpose

DynamicForms is the first canonical full-stack domain module on Core Platform. It serves two roles simultaneously:

1. **A product feature** — enabling tenants to create schema-driven forms, collect submissions, and access submitted data via API.
2. **A reference implementation** — demonstrating the correct, opinionated use of every platform convention: tenant-safe routing, modular service providers, CQRS-lite commands/queries, policy-based authorization, async-safe queues, and frontend schema rendering.

Every future module should look at DynamicForms first.

---

## Scope

### In scope (this architecture freeze)

- Tenant-owned form definitions
- Versioned form schemas
- Schema-driven field definitions (structured JSON)
- Form submissions that preserve schema at time of submission
- Backend validation against the active schema version
- Policy-based authorization for form management and submission
- REST API for all CRUD operations
- Frontend schema renderer (composable-based, TanStack Query aligned)
- Field renderer registry (extensible, not exhaustive)

### Out of scope (deferred — see known-issues.md)

- No-code visual form builder UI
- Workflow engine / multi-step approval flows
- Public external form sharing (unauthenticated submissions)
- File upload fields (placeholder in schema only)
- Repeater field implementation (documented as future)
- AI-assisted form generation
- Webhooks or integrations triggered on submission
- Exporting submissions to CSV/PDF
- Email notifications on submission

---

## Position in the Platform

```
Core Platform
├── Tenancy            ← infrastructure
├── IdentityAuth       ← infrastructure
├── Projects           ← domain module (existing)
└── DynamicForms       ← canonical domain module (this)
```

DynamicForms depends on:
- `TenantContextContract` from Tenancy module
- `CoreModuleServiceProvider` base class
- `TenantRouteRegistrar` for route registration
- Platform queue infrastructure for async safety

DynamicForms does NOT depend on:
- Projects module
- Any other domain module

---

## Key Architectural Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Schema storage | JSON column on `form_versions` | Structured, queryable, versionable |
| Versioning strategy | Immutable versions — new version = new record | Submissions reference a frozen snapshot |
| Tenant isolation | `tenant_id` on every table, scoped globally | Matches platform tenancy convention |
| Authorization | Laravel Policies + platform roles | Consistent with all other modules |
| Submission validation | Server validates payload against version schema | Client validation is UX-only |
| Soft deletes | Forms use `archived_at` column, not hard delete | Submissions must remain reachable |
| Async operations | None in V1 — submissions are synchronous | Queue introduced when webhooks/notifications added |
| Frontend rendering | Schema-driven field renderer registry | Forms render without hardcoded field lists |

---

## Relationship to Frontend Architecture

DynamicForms is the proof of the frontend architecture conventions established in Block 6.3:

- API access through `modules/dynamic-forms/api/`
- All server state through TanStack Query
- Schema-driven rendering via the field renderer registry defined in `shared/lib/fieldRenderers.ts`
- Two-layer validation: Zod-derived client rules + Laravel server error mapping
- Composables: `useFormList`, `useForm`, `useFormCreate`, `useFormSubmit`

---

## Non-Goals (Architecture Philosophy)

DynamicForms is not:

- A full no-code platform
- A workflow engine
- A CMS
- A survey tool with analytics
- A form-as-a-service product

It is a structured, tenant-aware form management system that sets the pattern for how domain modules behave on Core Platform.
