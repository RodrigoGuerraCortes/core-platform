# Core Platform — Folder Structure

## 1. Purpose

This document defines the official filesystem structure for:

- backend
- frontend
- modules
- infrastructure
- AI tooling
- documentation
- scaffolding

All repository organization should follow these conventions unless explicitly justified otherwise.

---

## 2. Repository Philosophy

- explicit structure over implicit organization
- modular ownership
- operational simplicity
- AI‑friendly filesystem layout
- maintainability over clever organization
- future scalability without premature complexity

---

## 3. Official Repository Structure

```
/
├── backend
├── frontend
├── docs
├── infra
├── scripts
├── tools
└── storage
```

**backend** – Laravel application and modular backend system.

**frontend** – Vue 3 + TypeScript frontend application.

**docs** – architecture, ADRs, OpenAPI, operational docs.

**infra** – Docker, deployment, infrastructure configs.

**scripts** – automation and maintenance scripts.

**tools** – AI tooling and engineering tooling.

**storage** – runtime‑generated artifacts and persistent runtime storage.

---

## 4. Backend Structure

```
backend/
├── app
│   ├── Core
│   ├── Domain
│   └── Shared
├── bootstrap
├── config
├── database
├── public
├── resources
│   └── prompts
├── routes
├── storage
└── tests
```

**Core** – platform‑wide reusable infrastructure modules.

**Domain** – business/domain applications.

**Shared** – small shared kernel only.

**resources/prompts** – filesystem‑first prompt infrastructure.

---

## 5. Namespace Structure

Official namespaces:

```
App\Core
App\Domain
App\Shared
```

Namespaces must reflect filesystem ownership consistently.

---

## 6. Core Module Structure

```
backend/app/Core/Identity/
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
├── Database
├── Filament
└── README.md
```

Modules own their internals.

---

## 7. Domain Structure

```
backend/app/Domain/MYLTracker/
├── Matches
├── Decks
├── Players
└── Tournaments
```

Domain applications follow the same modular conventions as Core modules.

---

## 8. Shared Kernel Rules

`Shared/` must remain intentionally small.

**Allowed examples:**

- shared DTOs
- shared contracts
- shared abstractions
- shared helpers

**Forbidden:**

- business logic dumping
- giant utility folders
- hidden dependencies

---

## 9. Frontend Structure

```
frontend/
├── src
├── public
├── tests
├── components
├── modules
└── services
```

- Vue 3 + TypeScript is the official frontend stack.
- Frontend should remain modular.
- Frontend modules should align conceptually with backend modules.

---

## 10. Prompt Structure

Official prompt strategy: **filesystem‑first**

```
backend/resources/prompts/
```

- Prompts are versioned assets.
- Prompts should remain reviewable.
- Prompts belong in source control initially.

---

## 11. Infrastructure Structure

```
infra/
├── docker
├── nginx
├── deployment
└── future
```

Infrastructure artifacts should remain centralized and explicit.

---

## 12. Scripts Structure

```
scripts/
├── setup
├── maintenance
├── migrations
└── local
```

Scripts improve operational consistency and onboarding.

---

## 13. Tools Structure

```
tools/
├── ai
├── generators
├── analysis
└── automation
```

Engineering tooling should remain separated from business code.

---

## 14. Storage Rules

Runtime‑generated artifacts must remain outside source‑controlled architecture.

Examples:

- uploads
- AI outputs
- generated media
- exports

Generated runtime artifacts should not be committed to source control.

---

## 15. Tenant Storage Structure

Recommended structure:

```
storage/app/tenants/{tenantId}/
```

Tenant‑generated files must remain isolated.

---

## 16. OpenAPI Structure

```
docs/openapi/
```

API contracts should remain documentable and versionable.

---

## 17. Tests Structure

```
backend/tests/
├── Feature
├── Integration
└── E2E
```

- Global integration tests may coexist with module‑local tests.
- Modules remain self‑testable.

---

## 18. AI‑Assisted Development Rules

- AI‑generated code must respect filesystem ownership.
- Scaffolding must follow these structures.
- Generated code should remain explicit.
- Consistency is mandatory.

---

## 19. Future Evolution

Future repository expansion may include:

- multiple frontend apps
- mobile apps
- distributed runtimes
- additional tooling

However, the platform currently prioritizes:

- operational simplicity
- modular monolith execution
- predictable structure

---

## 20. Final Statement

Core Platform filesystem organization prioritizes explicit ownership, modularity, AI‑assisted development consistency, and operational simplicity while supporting long‑term evolution.
