# Tenancy Flows

## Purpose

This document defines the official runtime flows of the Tenancy module.

The goal is to describe how organizational context moves through the platform during request execution and asynchronous operations.

These flows define:

* request lifecycle
* tenant resolution lifecycle
* membership validation
* platform vs tenant execution
* queue propagation
* future onboarding behavior

This document intentionally focuses on runtime behavior instead of implementation details.

---

# Core Principle

Every tenant-aware operation follows the same conceptual lifecycle:

```text id="dfb8me"
Resolve Context
→ Validate Access
→ Execute Business Logic
→ Preserve Isolation
```

Organizational isolation must exist before business execution begins.

---

# Flow Categories

The platform distinguishes between:

1. Platform flows
2. Tenant-aware flows
3. Async flows
4. Future onboarding flows

---

# Tenant-Aware Request Flow

## Overview

This is the standard request lifecycle for tenant-owned operations.

---

## Runtime Sequence

```text id="u0c6x6"
Incoming Request
→ ResolveTenant Middleware
→ TenantContext Initialization
→ Authentication
→ Membership Validation
→ Authorization
→ Business Execution
→ Response
```

---

## Example Request

```http id="r1axiu"
GET /api/projects
Authorization: Bearer xxx
X-Tenant-Id: tenant_123
```

---

## Detailed Flow

### Step 1 — Request Enters Platform

The request enters the Laravel application.

At this point:

* tenant context is unknown
* user identity may still be unknown
* authorization has not executed

---

### Step 2 — Tenant Resolution

The `ResolveTenant` middleware executes.

Responsibilities:

* read tenant identifier
* validate format
* resolve tenant entity
* validate tenant status
* reject invalid tenants

Example:

```http id="s7w7ju"
X-Tenant-Id: tenant_123
```

---

### Step 3 — TenantContext Initialization

The middleware initializes:

```php id="mq4s1h"
TenantContext
```

Example:

```php id="7t6y2d"
app(TenantContext::class)->setTenant($tenant);
```

From this point forward:

all tenant-aware runtime behavior consumes TenantContext.

---

### Step 4 — Authentication

Authentication resolves WHO the user is.

Examples:

* Sanctum token
* session authentication
* future OAuth flows

Authentication remains tenant-agnostic.

---

### Step 5 — Membership Validation

The platform validates:

```text id="l7r0d4"
Does the authenticated user belong to the resolved tenant?
```

Example:

```text id="8nl9p7"
User belongs to Tenant A
Request targets Tenant B
→ reject request
```

Expected response:

```http id="jlwm71"
403 Forbidden
```

---

### Step 6 — Authorization

Authorization evaluates capabilities inside the resolved tenant context.

Examples:

* roles
* permissions
* policies
* future RBAC

Authorization depends on:

* authenticated identity
* resolved tenant context

---

### Step 7 — Business Execution

Business logic executes.

Examples:

* controllers
* actions
* queries
* services
* events

All tenant-aware operations must consume:

```php id="47l1r2"
TenantContext
```

instead of raw request state.

---

### Step 8 — Response

The application returns the response.

The request lifecycle ends.

TenantContext is discarded automatically with request termination.

---

# Platform Request Flow

## Overview

Platform routes operate outside tenant context.

Examples:

```text id="0z10uk"
/admin
/platform/*
/health
/metrics
```

These routes operate under:

```text id="sfxi4x"
platform context
```

instead of:

```text id="5ghy4r"
tenant context
```

---

## Runtime Sequence

```text id="b8xwm7"
Incoming Request
→ Authentication
→ Platform Authorization
→ Operational Execution
→ Response
```

---

## Important Rule

Platform routes may bypass tenant resolution.

However:

platform administrators do NOT automatically bypass tenant isolation for tenant-owned data.

---

# Membership Validation Flow

## Overview

Membership validation ensures organizational isolation.

---

## Runtime Sequence

```text id="7b9mms"
Authenticated User
→ Tenant Resolved
→ Membership Lookup
→ Membership Validated
→ Continue Request
```

---

## Rejection Example

```text id="uwnjlwm"
User:
- member of Tenant A

Request:
- targets Tenant B

Result:
- reject request
```

Expected response:

```http id="2p3n5n"
403 Forbidden
```

---

# Queue Propagation Flow

## Overview

Tenant context must survive asynchronous execution.

Examples:

* queued jobs
* retries
* notifications
* scheduled tasks
* event listeners

---

## Runtime Sequence

```text id="3k3k79"
HTTP Request
→ TenantContext Active
→ Dispatch Job
→ Serialize tenant_id
→ Queue Worker
→ Restore TenantContext
→ Execute Job
```

---

## Important Rule

Jobs must never assume tenant context exists automatically.

Tenant context must always be restored explicitly before execution.

---

# Cache Flow

## Overview

Tenant-owned cache must remain isolated.

---

## Runtime Sequence

```text id="pzz40n"
Resolve Tenant
→ Build Tenant Cache Key
→ Read/Write Cache
```

---

## Example

```text id="s6cwlr"
tenant:tenant_123:settings
```

---

# Event Flow

## Overview

Tenant-aware events must preserve organizational context.

---

## Runtime Sequence

```text id="xoyvli"
Resolve Tenant
→ Execute Business Logic
→ Dispatch Event
→ Attach tenant metadata
→ Event Listener
```

---

## Event Metadata Example

```json id="g8y7ph"
{
  "tenant_id": "tenant_123",
  "user_id": 5
}
```

---

# Observability Flow

## Overview

Tenant context should eventually enrich operational telemetry.

Examples:

* logs
* traces
* metrics
* audit events

---

## Runtime Sequence

```text id="p11w4j"
Resolve Tenant
→ Attach Tenant Metadata
→ Execute Operation
→ Emit Telemetry
```

---

# Future Tenant Creation Flow

## Current Strategy

Initial tenant provisioning is platform-admin-driven.

Example:

```text id="l4j38l"
Platform Admin
→ Create Tenant
→ Invite Users
→ Users Join Tenant
```

---

## Future Self-Service Flow

Potential future flow:

```text id="slhnm9"
User Signup
→ Tenant Provisioning
→ Owner Membership
→ Initial Setup
```

This flow is intentionally postponed until:

* notifications
* onboarding
* billing
* invitations
* audit logging

become mature.

---

# Tenant Switching Philosophy

The platform intentionally avoids persistent active tenant state.

There is no:

```text id="1z52l4"
Current Organization Session
```

Tenant context must always be explicit per request.

This prevents:

* session inconsistency
* multi-tab bugs
* hidden organizational state
* race conditions

---

# Failure Flows

## Missing Tenant

```text id="p0otw7"
Request
→ Missing Tenant Header
→ Reject Immediately
```

---

## Invalid Tenant

```text id="40tbjx"
Request
→ Invalid Tenant
→ Reject Immediately
```

---

## Unauthorized Membership

```text id="jzq6u8"
Request
→ User Not Member
→ Reject Immediately
```

---

# Long-Term Goals

The runtime flow architecture is designed to support:

* SaaS applications
* AI agents
* modular domains
* async workers
* future distributed systems
* future API gateways

without changing organizational behavior.

---

# Final Principle

Every tenant-aware operation must explicitly know:

```text id="s9g9na"
WHO
WHERE
WHAT
```

before business execution begins.

Identity determines WHO.

Tenancy determines WHERE.

Authorization determines WHAT.
