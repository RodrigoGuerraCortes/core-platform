# Core Platform — Module Map

## 1. Purpose

This document defines the official module organization strategy for Core Platform.

The objective is to establish:

- module boundaries
- architectural responsibilities
- dependency rules
- communication patterns
- domain isolation
- reusable infrastructure standards

This document is considered one of the foundational architectural references of the platform.

---

## 2. Architectural Overview

Core Platform follows a:

# Modular Monolith Architecture

The platform is composed of:

- reusable core infrastructure modules
- isolated domain applications
- controlled shared abstractions

The system operates as a single deployable application while maintaining internal modular boundaries.

---

## 3. High-Level Structure

Official application structure:

```txt
app/
├── Core/
├── Domain/
└── Shared/
```

---

## 4. Core Responsibilities

The `Core/` layer contains reusable platform infrastructure.

Core modules must NEVER contain business-specific concepts.

Core modules are reusable across all domain applications.

---

## 5. Domain Responsibilities

The `Domain/` layer contains business applications and bounded contexts.

Examples:

- Marketplace
- Parcelas
- MYLTracker
- CRM
- Inventory
- ERP

Each domain module owns its:

- business rules
- entities
- workflows
- policies
- actions
- events
- queries

---

## 6. Shared Responsibilities

The `Shared/` layer contains minimal cross-platform abstractions.

Shared must remain small and highly controlled.

Examples:

- DTO contracts
- base abstractions
- shared enums
- shared interfaces
- common utilities

The platform must avoid turning Shared into a dumping ground.

---

## 7. Official Directory Structure

### 7.1 Root Structure

```txt
app/
├── Core/
├── Domain/
└── Shared/
```

### 7.2 Core Structure

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
├── Menu/
├── Settings/
├── Webhooks/
├── Observability/
└── AI/
```

### 7.3 AI Structure

```txt
Core/AI/
├── Hooks/
├── Providers/
├── Prompts/
├── Templates/
├── Agents/
├── Orchestrators/
├── Pipelines/
├── Context/
├── DTOs/
├── Events/
├── Actions/
└── Queries/
```

### 7.4 Domain Structure

```txt
Domain/
├── Marketplace/
├── Parcelas/
├── MYLTracker/
└── CRM/
```

---

## 8. Official Internal Module Structure

Example:

```txt
Marketplace/
├── Actions/
├── Queries/
├── DTOs/
├── Events/
├── Listeners/
├── Policies/
├── Models/
├── Services/
├── Controllers/
├── Requests/
├── Resources/
├── Jobs/
├── Rules/
├── Exceptions/
├── Enums/
├── Mappers/
├── Pipelines/
└── Tests/
```

Not all folders are required in every module.

Modules should remain as simple as possible.

---

## 9. Architectural Philosophy

### 9.1 Module-Oriented Architecture

The platform officially adopts:

# Module-Oriented Architecture

Avoid organizing the application globally by technical layers.

Avoid:

```txt
Controllers/
Services/
Repositories/
Models/
```

at the root application level.

Instead, all code should belong to a module.

---

## 10. Dependency Rules

### 10.1 Core Rules

Core modules:

- may depend on Shared
- may communicate with other Core modules
- must never depend on Domain modules

### 10.2 Domain Rules

Domain modules:

- may depend on Core
- may depend on Shared
- must NOT directly depend on other Domain modules

### 10.3 Shared Rules

Shared:

- must contain no business logic
- must remain minimal
- must avoid framework-heavy implementations

---

## 11. Communication Rules

### 11.1 Preferred Communication Strategy

Preferred communication:

# Events First

Modules should prefer communication through:

- domain events
- async jobs
- listeners
- orchestration flows

when possible.

### 11.2 Synchronous Communication

Synchronous communication is allowed when:

- explicit contracts exist
- coupling remains controlled
- simplicity is preferable

Examples:

- DTO-based service calls
- query objects
- orchestration actions

---

## 12. Event Strategy

### 12.1 Internal Events

Examples:

```txt
UserCreated
TenantCreated
UploadCompleted
PromptExecuted
AIExecutionCompleted
OrderPaid
MatchRegistered
```

### 12.2 Async Event Processing

Async processing should use:

# Laravel Queues + Redis

---

## 13. CQRS-Lite Strategy

The platform officially adopts:

# CQRS-Lite

### 13.1 Actions

```txt
CreateTenantAction
RegisterMatchAction
CreateMarketplaceProductAction
```

### 13.2 Queries

```txt
GetTenantUsersQuery
ListTenantProductsQuery
GetAuditHistoryQuery
```

### 13.3 DTOs

DTOs are mandatory between modules and orchestration layers.

---

## 14. Repository Strategy

Repositories are NOT mandatory.

Preferred approach:

- Actions
- Queries
- Services
- DTOs

Repositories should only exist when they provide clear value.

---

## 15. Policy Strategy

### 15.1 Centralized Core Authorization

Core authorization manages:

- platform administration
- tenant isolation
- global roles
- shared access control

### 15.2 Domain Authorization

Example:

```txt
Domain/Marketplace/Policies/
Domain/MYLTracker/Policies/
```

---

## 16. Multi-Domain Strategy

Core Platform is designed as:

# A Multi-Domain Platform

The objective is NOT to clone repositories for every new system.

Instead, new domain applications should be added as modules inside the platform.

---

## 17. Future Extraction Strategy

Potential extraction candidates:

- AI runtimes
- orchestration engines
- ETL pipelines
- realtime systems
- high concurrency services

Premature distributed architecture is explicitly discouraged.

---

## 18. Naming Conventions

### 18.1 Modules

```txt
Marketplace
TenantManagement
PromptExecution
```

### 18.2 Actions

Must end with:

```txt
Action
```

### 18.3 Queries

Must end with:

```txt
Query
```

### 18.4 DTOs

Must end with:

```txt
Data
DTO
```

### 18.5 Events

Use past tense:

```txt
UserCreated
PromptExecuted
UploadCompleted
```

---

## 19. Architectural Goals

The module architecture must maximize:

- maintainability
- clarity
- AI-assisted development
- modularity
- scalability
- developer productivity
- operational consistency

while minimizing:

- accidental coupling
- duplicated infrastructure
- architectural chaos
- operational complexity
