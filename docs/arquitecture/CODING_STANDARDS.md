# Core Platform — Coding Standards

## 1. Purpose

This document defines the official implementation standards for:

- developers
- AI tools
- code reviews
- scaffolding
- module consistency

All implementation should follow these standards unless an ADR explicitly overrides them.

---

## 2. Core Philosophy

- explicit code over magic
- maintainability over cleverness
- consistency over personal preference
- modularity over global coupling
- pragmatism over enterprise complexity
- AI‑friendly structure by design

---

## 3. Language & Framework Standards

Official stack:

- PHP 8+
- Laravel
- PostgreSQL
- Redis
- Vue 3
- TypeScript
- Pest
- Filament (admin acceleration layer)

**Filament** is an acceleration layer for administrative/backoffice tooling, not the core frontend architecture. **Vue 3** remains the official frontend application framework.

---

## 4. Strict Typing

```php
declare(strict_types=1);
```

is required for all PHP files where applicable.

---

## 5. Actions Standard

Use **Actions** for business use cases.

Examples:

- `CreateUserAction`
- `UpdateTenantSettingsAction`
- `RegisterMatchResultAction`

- Actions represent explicit business operations.
- Actions should be small and focused.
- Actions should be testable.
- Actions should avoid hidden side effects.

---

## 6. Queries Standard

Read operations should use **Queries**.

Examples:

- `GetTenantUsersQuery`
- `GetMarketplaceProductsQuery`

- Queries are read‑only.
- Queries should not mutate state.
- Queries should remain focused and predictable.

---

## 7. DTO Standard

DTOs are mandatory between layers/modules.

Examples:

- `CreateUserDTO`
- `UpdateProductDTO`

- DTOs define explicit contracts.
- DTOs improve AI‑assisted development.
- DTOs reduce hidden coupling.

---

## 8. Controller Standard

Controllers must remain ultra‑lightweight.

Official flow:

```
Controller
→ FormRequest
→ DTO
→ Action/Query
→ Resource
```

Controllers must **NOT** contain:

- business logic
- orchestration
- complex validation
- tenant resolution logic

---

## 9. Form Request Standard

Laravel Form Requests are officially required for request validation.

- Validation must remain centralized.
- Controllers should not validate manually.

---

## 10. API Resource Standard

Laravel API Resources are officially required for API responses.

- Responses must remain consistent.
- API shape should be controlled centrally.

---

## 11. Model Standard

Eloquent models must remain lightweight.

**Allowed:**

- relationships
- casts
- scopes
- small helpers

**Forbidden:**

- orchestration
- heavy business logic
- workflow coordination

---

## 12. Repository Standard

Repositories are **NOT** mandatory.

Official preference:

- Actions
- Queries
- DTOs

Repositories should only exist when they add real value, for example:

- reusable complex queries
- external provider abstraction
- infrastructure‑specific persistence

---

## 13. Service Standard

Services should exist only for:

- shared complex logic
- infrastructure logic
- reusable domain coordination

Do **NOT** create generic CRUD services like `UserService->create()` or `ProductService->update()` when Actions already exist.

---

## 14. Event Standard

Events should primarily exist for async boundaries.

Examples:

- notifications
- AI execution
- uploads
- integrations
- long‑running workflows

Direct synchronous calls are allowed and encouraged when simpler.

**Avoid:**

- event explosion
- unnecessary orchestration
- hidden async coupling

---

## 15. Authorization Standard

Policies are mandatory.

Strategy:

- core base policies
- extensible by module/domain

Authorization must remain:

- explicit
- tenant‑aware
- centralized

---

## 16. Multi‑Tenant Rules

All business logic must be tenant‑aware by default.

**Forbidden:**

- bypassing tenant isolation
- unscoped tenant queries
- cross‑tenant access without explicit authorization

---

## 17. UUID Standard

UUIDs are the official identifier strategy.

- Public entities should prefer UUIDs.
- IDs should remain opaque externally.

---

## 18. Soft Delete Standard

Soft deletes are recommended for most business entities.

- Not mandatory for every table.
- Depends on business requirements.

---

## 19. Enum Standard

PHP Enums are officially encouraged.

Examples:

- statuses
- states
- types

Avoid magic strings in business logic.

---

## 20. Final Class Standard

Classes should be `final` by default unless framework extension requires otherwise.

---

## 21. Testing Standard

Pest is the official testing framework.

Testing is mandatory for:

- Actions
- Queries
- Policies
- critical business rules

- Tests are part of implementation.
- AI‑generated code must include tests when appropriate.

---

## 22. Naming Conventions

Examples:

- `CreateUserAction`
- `GetTenantUsersQuery`
- `UserDTO`
- `UserResource`
- `UserCreatedEvent`

Naming must remain:

- explicit
- descriptive
- predictable

---

## 23. Folder Structure Standard

Official example:

```
Core/Identity/
├── Actions
├── DTOs
├── Events
├── Exceptions
├── Models
├── Policies
├── Queries
├── Resources
├── Services
├── Tests
```

- Modules own their internals.
- Boundaries must remain explicit.

---

## 24. AI‑Assisted Development Rules

- AI tools must follow these conventions.
- AI‑generated code must remain reviewable.
- Humans own final decisions.
- Consistency is mandatory.

---

## 25. Final Statement

Core Platform prioritizes explicit, maintainable, AI‑friendly implementation standards designed for long‑term evolution and small‑team effectiveness.
