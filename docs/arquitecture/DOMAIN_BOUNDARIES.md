# Core Platform — Domain Boundaries

## 1. Purpose

This document defines the official domain boundary strategy for Core Platform.

The objective is to clearly separate:

- reusable platform infrastructure
- business domains
- bounded contexts
- shared abstractions
- operational capabilities

---

## 2. Architectural Philosophy

Core Platform is a reusable platform capable of hosting multiple domain applications inside a unified architecture.

The platform must maintain strict separation between:

- platform capabilities
- business logic
- tenant-specific behavior
- domain-specific workflows

---

## 3. Core vs Domain Separation

```txt
app/
├── Core/
├── Domain/
└── Shared/
```

### Core

Reusable infrastructure and platform capabilities.

### Domain

Business applications and bounded contexts.

### Shared

Minimal cross-cutting abstractions.

---

## 4. Core Responsibilities

Core modules provide reusable capabilities shared across all domain applications.

Core modules must NEVER contain business-specific logic.

```txt
Core/
├── Identity/
├── Authorization/
├── Tenancy/
├── Audit/
├── Notifications/
├── Uploads/
├── Workers/
├── Dashboard/
├── Settings/
├── Menu/
├── Webhooks/
├── Observability/
└── AI/
```

---

## 5. Domain Responsibilities

```txt
Domain/
├── Marketplace/
├── Parcelas/
├── MYLTracker/
├── CRM/
└── Inventory/
```

Each domain owns:

- entities
- business rules
- workflows
- policies
- AI implementations
- jobs
- orchestration

---

## 6. Bounded Context Strategy

The platform officially adopts explicit bounded contexts.

Example:

```txt
Marketplace/
├── Products/
├── Orders/
├── Payments/
└── Inventory/
```

Each bounded context should own:

- actions
- queries
- policies
- events
- DTOs
- workflows

---

## 7. Shared Kernel Rules

```txt
Shared/
├── Contracts/
├── DTOs/
├── Enums/
├── Exceptions/
└── Utilities/
```

Shared MUST NOT contain:

- business workflows
- domain-specific rules
- hidden dependencies

---

## 8. AI Boundary Strategy

### Core AI Responsibilities

```txt
Core/AI/
├── Providers/
├── PromptEngine/
├── Templates/
├── Hooks/
├── Orchestrators/
├── Agents/
├── Pipelines/
└── Context/
```

### Domain AI Responsibilities

```txt
Domain/Marketplace/AI/
Domain/MYLTracker/AI/
Domain/Parcelas/AI/
```

---

## 9. Dashboard Boundary Strategy

Dashboard infrastructure belongs to Core.

```txt
Core/Dashboard/
```

Domains may contribute widgets.

```txt
Domain/Marketplace/Dashboard/
Domain/MYLTracker/Dashboard/
```

---

## 10. Settings Boundary Strategy

The platform supports:

- global platform settings
- tenant settings
- domain settings

---

## 11. Webhook Boundary Strategy

Webhook infrastructure belongs to Core.

```txt
Core/Webhooks/
```

Domains may register webhook handlers.

---

## 12. Worker Boundary Strategy

### Core Workers

Core owns generic infrastructure workers.

### Domain Workers

Domains own business-specific jobs.

```txt
Domain/Marketplace/Jobs/
Domain/MYLTracker/Jobs/
```

---

## 13. Authorization Boundary Strategy

### Platform Authorization

Core authorization manages:

- platform administrators
- tenant isolation
- global roles
- global permissions

### Domain Authorization

Domains manage business-specific permissions.

---

## 14. Platform Roles

```txt
Platform Admin
Tenant Admin
Tenant User
```

---

## 15. Reporting & Operational Views

Operational reporting infrastructure belongs to Core.

```txt
Core/Observability/
Core/Reporting/
```

Domains may expose reporting views.

---

## 16. Dependency Rules

### Allowed Dependencies

```txt
Domain -> Core
Domain -> Shared
Core -> Shared
```

### Forbidden Dependencies

```txt
Domain -> Domain
Core -> Domain
```

Communication should occur through:

- events
- contracts
- orchestration
- APIs
- DTOs

---

## 17. Anti-Patterns

The following anti-patterns are discouraged:

- business logic inside Core
- massive Shared layer
- hidden cross-domain coupling
- uncontrolled global helpers

---

## 18. Future Extraction Strategy

Potential extraction candidates:

- AI runtimes
- realtime services
- orchestration engines
- ETL systems
- streaming pipelines

Premature distributed architecture is discouraged.

---

## 19. Architectural Goals

The domain boundary strategy exists to maximize:

- maintainability
- scalability
- modularity
- domain isolation
- AI-assisted development
- operational clarity

while minimizing:

- accidental coupling
- duplicated logic
- architectural chaos
