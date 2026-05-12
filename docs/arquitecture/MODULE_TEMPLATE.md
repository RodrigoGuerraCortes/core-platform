# Core Platform — Module Template

## 1. Purpose

This document defines the official module structure used by:

- Core modules
- Domain applications
- future modules
- AI‑assisted scaffolding

All modules should follow this structure unless explicitly justified otherwise.

---

## 2. Core Philosophy

- modules own their internals
- boundaries must remain explicit
- modules should minimize coupling
- modules should remain testable
- modules should remain AI‑friendly
- modules should remain future‑extractable conceptually
- modularity is preferred over global organization

---

## 3. Official Root Structure

```
app/
├── Core
├── Domain
└── Shared
```

**Core** – platform‑wide reusable infrastructure.

**Domain** – business/domain applications.

**Shared** – small shared kernel only.

Shared must remain intentionally minimal.

---

## 4. Core Module Example

```
Core/Identity/
├── Actions
├── DTOs
├── Events
├── Exceptions
├── Http
│   ├── Controllers
│   ├── Requests
│   └── Resources
├── Listeners
├── Models
├── Policies
├── Providers
├── Queries
├── Services
├── Tests
├── Routes
│   └── api.php
├── Database
│   ├── Migrations
│   └── Seeders
├── Filament
└── README.md
```

Not every module requires every folder. Modules should include only what they actually need.

---

## 5. Domain Module Example

```
Domain/MYLTracker/
├── Matches
├── Decks
├── Players
└── Tournaments
```

Nested module example:

```
Domain/MYLTracker/Matches/
├── Actions
├── DTOs
├── Events
├── Http
├── Models
├── Policies
├── Queries
├── Tests
└── README.md
```

Domain applications follow the same conventions as Core modules.

---

## 6. Module Ownership Rules

- modules own their internals
- internal classes should not be accessed directly from other modules
- cross‑module communication should prefer:

  - DTOs
  - contracts
  - events
  - public APIs

Avoid hidden coupling.

---

## 7. HTTP Layer Rules

Controllers, Requests, and Resources must live inside the module.

```
Http/
├── Controllers
├── Requests
└── Resources
```

The HTTP layer belongs to the module itself.

---

## 8. Database Rules

Database artifacts should live inside the module.

```
Database/
├── Migrations
└── Seeders
```

This improves ownership, future extraction, and modular clarity.

---

## 9. Routes Rules

Routes should live inside the module.

```
Routes/api.php
```

Modules own their routes.

---

## 10. Service Provider Rules

Every significant module should have its own Service Provider.

Example: `IdentityServiceProvider`

Responsibilities may include:

- routes
- policies
- events
- bindings
- migrations

---

## 11. Tests Rules

Tests should live inside the module.

```
Tests/
```

Modules should remain self‑contained and testable.

---

## 12. Filament Rules

Filament resources should remain inside the owning module.

```
Filament/
```

Filament is an admin acceleration layer, not the primary frontend architecture.

---

## 13. AI Folder Rules

Modules may contain AI‑specific folders only when required.

```
AI/
├── Prompts
├── Hooks
├── Agents
```

AI infrastructure should remain intentional and scoped.

---

## 14. Shared Kernel Rules

`Shared/` should remain intentionally small.

Allowed examples:

- base DTOs
- common contracts
- shared abstractions
- common helpers

Forbidden:

- business logic leakage
- giant utility dumping ground
- hidden global dependencies

---

## 15. Future Extraction Philosophy

Modules should be designed so they could theoretically be extracted in the future.

However, do **NOT** prematurely optimize for microservices.

The platform officially prioritizes:

- modular monolith simplicity
  over
- distributed complexity

---

## 16. AI‑Assisted Development Rules

- AI tools must follow this structure
- scaffolding should respect module boundaries
- generated code must remain explicit
- consistency is mandatory

---

## 17. Module README Rules

Every significant module should contain a `README.md` explaining:

- purpose
- boundaries
- public APIs
- important workflows
- integrations

Module READMEs improve maintainability and AI‑assisted development.

---

## 18. Final Statement

Core Platform modules are designed to remain explicit, maintainable, AI‑friendly, and operationally simple while supporting long‑term evolution.
