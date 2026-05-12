# Core Platform — Database Conventions

## 1. Purpose

This document defines the official database conventions for:

- persistence
- migrations
- indexing
- multi‑tenancy
- auditability
- AI‑assisted development

All database design should follow these conventions unless explicitly justified otherwise.

---

## 2. Database Philosophy

- explicit schemas over implicit structures
- tenant‑aware by default
- maintainability over premature optimization
- predictable migrations
- auditability first
- operational simplicity
- scalable conventions without distributed complexity

---

## 3. Database Technology

Official database stack:

- **PostgreSQL** – primary relational database
- **Redis** – queues, cache, and transient infrastructure concerns

---

## 4. Identifier Strategy

Official identifier strategy:

**ULID**

ULID is the single official identifier strategy for public/business entities.

- ULIDs are preferred for public/business entities.
- Sortable identifiers improve indexing behavior.
- Identifiers should remain opaque externally.
- Internal numeric IDs may exist internally but must never be exposed publicly.

---

## 5. Tenant Isolation Rules

`tenant_id` is mandatory for all tenant‑owned business entities.

Exceptions may include:

- platform configuration
- static catalogs
- global infrastructure tables

- Tenant isolation is mandatory.
- Database design must reinforce tenant safety.
- Cross‑tenant leakage is forbidden.

---

## 5.1 Tenant Isolation Enforcement

Core Platform enforces tenant isolation through multiple layers:

### A. Global Tenant Scopes

- Tenant‑owned entities should use global Eloquent tenant scopes by default.
- Cross‑tenant queries require explicit bypass mechanisms.
- Bypasses should remain highly restricted and auditable.

Valid bypass scenarios include:

- platform admin workflows
- operational tooling
- maintenance workflows

### B. Tenant Middleware

- Tenant resolution middleware is mandatory.
- Requests must resolve tenant context before business execution.
- Unresolved tenant context should fail fast.

### C. Tenant Architecture Tests

- Automated tests must validate tenant isolation.
- Cross‑tenant leakage tests are mandatory.
- Critical modules require tenant isolation test coverage.

Examples:

- tenant A cannot access tenant B data
- filters cannot bypass tenant scope
- includes cannot bypass tenant scope

### D. Static Analysis Rules

- Static analysis should help detect unscoped tenant queries.
- Dangerous bypass patterns should be minimized.
- Explicit tenant bypasses should remain reviewable.

### E. Documentation Clarifications

- Tenant isolation is a platform‑level invariant, not an implementation detail.

---

## 5.1 Tenant Isolation Enforcement

Core Platform enforces tenant isolation through multiple layers:

### A. Global Tenant Scopes

- Tenant‑owned entities should use global Eloquent tenant scopes by default.
- Cross‑tenant queries require explicit bypass mechanisms.
- Bypasses should remain highly restricted and auditable.

Valid bypass scenarios include:

- platform admin workflows
- operational tooling
- maintenance workflows

### B. Tenant Middleware

- Tenant resolution middleware is mandatory.
- Requests must resolve tenant context before business execution.
- Unresolved tenant context should fail fast.

### C. Tenant Architecture Tests

- Automated tests must validate tenant isolation.
- Cross‑tenant leakage tests are mandatory.
- Critical modules require tenant isolation test coverage.

Examples:

- tenant A cannot access tenant B data
- filters cannot bypass tenant scope
- includes cannot bypass tenant scope

### D. Static Analysis Rules

- Static analysis should help detect unscoped tenant queries.
- Dangerous bypass patterns should be minimized.
- Explicit tenant bypasses should remain reviewable.

### E. Documentation Clarifications

- Tenant isolation is a platform‑level invariant, not an implementation detail.

---

## 6. Foreign Key Rules

Foreign keys are officially required.

- Referential integrity is mandatory.
- Orphaned data should be avoided.
- Relationships must remain explicit.

---

## 7. Delete Strategy

Official strategy:

**Prefer:**

- soft deletes
- explicit workflows

**Avoid:**

- aggressive cascade deletes
- hidden destructive behavior

Large cascade delete chains increase operational risk.

---

## 8. Timestamp Standard

Official timestamp fields:

- `created_at`
- `updated_at`
- `deleted_at`

Laravel timestamp conventions should remain standard.

---

## 9. Audit Column Standard

Recommended audit fields for important entities:

- `created_by`
- `updated_by`
- `deleted_by`

Critical business entities should remain auditable.

---

## 10. Soft Delete Standard

Soft deletes are officially recommended for most business entities.

- Not every table requires soft deletes.
- Business requirements determine final behavior.
- External APIs should normally treat soft‑deleted entities as non‑existent.

---

## 11. Naming Convention Rules

Official conventions:

**Tables:**

- snake_case
- plural

Examples:

- `tenant_users`
- `audit_logs`
- `ai_executions`

**Columns:**

- snake_case

Naming consistency is mandatory.

---

## 12. Pivot Table Rules

Pivot tables should follow alphabetical naming order.

Example:

`permission_role`

Naming conventions should remain predictable and consistent.

---

## 13. Enum Strategy

Official strategy:

- PHP Enums
- string‑backed database values

**Avoid:**

- database‑native enums initially

String‑backed enums simplify migrations and evolution.

---

## 14. JSON Column Rules

JSON columns are allowed when used intentionally.

**Good examples:**

- metadata
- AI payload summaries
- external payload snapshots
- flexible integration data

**Avoid:**

- business logic dumping
- unstructured persistence
- replacing relational design unnecessarily

---

## 15. Indexing Rules

Official indexing rules:

- `tenant_id` should normally be indexed
- foreign keys should normally be indexed
- frequently queried statuses should be indexed
- `created_at` may be indexed for operational queries

Indexes should support predictable operational behavior.

---

## 16. Composite Index Rules

Composite indexes are encouraged where appropriate.

Common example:

`tenant_id` + `ulid`

Tenant‑aware indexing improves scalability and isolation performance.

---

## 17. Unique Constraint Rules

Unique constraints should normally remain tenant‑aware.

Preferred example:

`tenant_id` + `email`

**Avoid:**

- unnecessary global uniqueness
- cross‑tenant coupling

---

## 18. Migration Rules

Migration philosophy:

- small migrations
- explicit migrations
- predictable migrations
- reversible migrations when possible

**Avoid:**

- giant migration files
- hidden destructive operations

---

## 19. Seeder Rules

Seeders are officially encouraged for:

- roles
- permissions
- catalogs
- reference data

Seeders improve consistency across environments.

---

## 20. Transaction Rules

Critical Actions should use database transactions when appropriate.

- Transactions should remain focused.
- Avoid giant transactional workflows.
- Operational simplicity is preferred.

---

## 21. AI Persistence Rules

AI execution persistence should remain intentional.

**Recommended:**

- summarized payloads
- execution metadata
- prompts used
- execution status
- audit references

**Avoid:**

- uncontrolled raw payload accumulation
- excessive storage growth

---

## 22. Upload Persistence Rules

Uploads should persist metadata such as:

- tenant ownership
- uploader
- mime type
- storage path
- checksum
- visibility

Uploads must remain tenant‑aware and auditable.

---

## 23. Event Sourcing Position

Event sourcing is **NOT** part of the official Phase 1 strategy.

The platform prioritizes:

- modular monolith simplicity
  over
- distributed/event‑store complexity

---

## 24. Future Evolution

The following are considered future evolution only:

- read replicas
- sharding
- partitioning
- advanced archival strategies

Examples:

- `audit_logs` partitioning
- `ai_executions` archival

The platform avoids premature distributed database complexity.

---

## 25. AI‑Assisted Development Rules

- AI‑generated migrations must follow these conventions.
- Tenant isolation must never be bypassed.
- Generated schemas must remain explicit.
- Consistency is mandatory.

---

## 26. Final Statement

Core Platform database conventions prioritize explicit structure, tenant safety, auditability, operational simplicity, and long‑term maintainability while supporting AI‑assisted development.
