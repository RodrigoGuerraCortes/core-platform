# Core Platform — Event Model

## Purpose

Define the official event-driven architecture strategy for Core Platform.

---

# Event Philosophy

Events are first-class architectural citizens.

Events enable:

- loose coupling
- async workflows
- orchestration
- scalability
- observability

---

# Event Types

## Domain Events

Represent business changes.

Examples:

```txt
OrderPaid
MatchRegistered
ProductPublished
```

## Platform Events

Represent infrastructure changes.

Examples:

```txt
TenantCreated
UserInvited
UploadCompleted
```

---

# Event Structure

Events should contain:

- event_id
- tenant_id
- actor_id
- event_name
- timestamp
- payload
- metadata

---

# Async Processing

Async processing uses:

```txt
Laravel Queues + Redis
```

---

# Event Naming

Events should use past tense.

Examples:

```txt
UserCreated
PromptExecuted
NotificationSent
```

---

# Event Boundaries

Domains must avoid directly depending on other domains.

Communication should prefer events.

---

# AI Events

Examples:

```txt
PromptExecutionStarted
PromptExecutionCompleted
AgentExecutionFailed
```

---

# Idempotency

Consumers should be idempotent whenever possible.

---

# Observability

Events should integrate with:

- logs
- metrics
- tracing
- audits

---

# Events for Async Boundaries

Events should primarily be used for:

- notifications (email, push, in‑app)
- AI execution (triggering a queued AI job)
- uploads (processing a file after it is stored)
- integrations (webhooks, third‑party sync)
- async workflows (long‑running processes that should not block the request)

**Direct synchronous calls inside the monolith are acceptable and encouraged when simpler.** Not every module interaction needs an event. Use events only when you need:

- decoupling (the caller does not need to wait for the result)
- retry capability (the operation may fail and should be retried)
- parallel execution (multiple independent handlers)

The platform should avoid:

- event explosion (creating an event for every minor state change)
- unnecessary async complexity (using events for operations that are fast and synchronous)
- orchestration sprawl (chaining events in a way that makes the flow hard to trace)
