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