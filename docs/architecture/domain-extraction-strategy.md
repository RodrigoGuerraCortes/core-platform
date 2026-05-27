# Domain Extraction Strategy

> Authoritative reference. Defines when and how domains may be extracted.
> Last updated: 2026-05-25

---

## Philosophy

**The platform is a modular monolith. Extraction is deferred until proven necessary.**

We do NOT extract because:
- "microservices are modern"
- "DDD says bounded contexts should be separate"
- "it might scale better someday"

We DO extract when:
- A concrete, measurable trigger is hit
- The cost of remaining monolithic exceeds the cost of splitting

Until then: **monolith with clean internal boundaries.**

---

## Why Monolith Today

| Reason | Explanation |
|--------|------------|
| Team size | Single team — no coordination overhead to justify |
| Deployment cadence | Single deploy pipeline is simpler and faster |
| Data consistency | Shared PostgreSQL avoids distributed transaction complexity |
| Auth simplicity | Single Sanctum session — no token federation needed |
| Development speed | One repo, one Docker stack, one test suite |
| Debugging | Telescope sees everything in one place |

---

## What Boundaries Already Exist

Despite being monolithic, the platform already enforces extraction-ready boundaries:

### Backend

| Boundary | Implementation |
|----------|---------------|
| Module isolation | `app/Core/<Domain>/` — each domain has its own directory tree |
| Migration isolation | `database/migrations/<domain>/` — no shared migration files |
| Seeder isolation | `database/seeders/<Domain>/` — each domain seeds independently |
| Route isolation | Each module registers routes via its ServiceProvider |
| Model isolation | Models live under their domain namespace |
| Controller isolation | Controllers live under their domain's `Http/Controllers/` |

### Frontend

| Boundary | Implementation |
|----------|---------------|
| Module isolation | `src/modules/<domain>/` — pages, composables, API, types |
| Experience isolation | `src/experiences/<domain>/` — navigation, branding |
| Navigation isolation | Each experience defines its own nav items |
| Route isolation | Each module exports its own route array |
| API isolation | Each module has its own `api/<domain>.ts` client |

### Cross-Cutting

| Boundary | Implementation |
|----------|---------------|
| No cross-vertical imports | Verticals never import from each other |
| Shared kernel | Only `@/shared/` and Core Platform code is shared |
| Event-based communication | Verticals communicate via domain events (when needed) |

---

## What Makes a Domain Extractable

A domain is extraction-ready when ALL of these are true:

- [ ] Zero direct PHP class imports from other verticals
- [ ] Zero direct frontend imports from other vertical modules
- [ ] Own migrations that don't JOIN with other vertical tables
- [ ] Own seeders that only depend on Core (not other verticals)
- [ ] Own API routes with no cross-vertical route dependencies
- [ ] Own test suite that passes independently
- [ ] No shared database tables (beyond `tenants`, `users`, core tables)
- [ ] Communication with other verticals only via events or API

---

## DO NOT (Premature Extraction Anti-Patterns)

| Anti-Pattern | Why It's Wrong |
|--------------|---------------|
| Split databases per vertical | Adds operational complexity with no current benefit |
| Separate Git repos per vertical | Increases coordination overhead for single team |
| Separate auth systems per vertical | Fragments identity management |
| API gateway between verticals | Adds latency and failure modes for internal calls |
| Event sourcing "just in case" | Massive complexity for uncertain future benefit |
| CQRS with separate read/write databases | Overkill at current scale |
| Kubernetes per-service deployment | Infrastructure cost exceeds value |

---

## Extraction Triggers

Extract a domain into a separate deployment ONLY when one of these triggers fires:

### 1. Compliance Isolation

**Trigger:** Regulatory requirement mandates that a domain's data must be physically isolated.

**Example:** Health data (MiniHIS) requires SOC2/HIPAA isolation that other domains don't need.

**Action:** Extract to separate database + deployment with own audit trail.

### 2. Independent Scaling

**Trigger:** One domain receives 10x+ the traffic of others and cannot scale with the monolith.

**Example:** CondoFlow ticket intake during emergencies (flood, earthquake).

**Action:** Extract the hot path into a scalable service.

### 3. Independent Release Cadence

**Trigger:** One domain needs to deploy multiple times daily while others deploy weekly.

**Example:** Forms engine iterating rapidly while CondoFlow is stable.

**Action:** Extract to own repo with own CI/CD.

### 4. Team Autonomy

**Trigger:** Separate teams working on separate domains with conflicting schedules.

**Example:** External team building MiniHIS needs full ownership.

**Action:** Extract to separate repo with clear API contracts.

### 5. Legal/Organizational Isolation

**Trigger:** Domain must be owned by a different legal entity or sold separately.

**Action:** Full extraction to independent system.

---

## Extraction Playbook (When Triggered)

1. **Document the trigger** — write an ADR explaining why extraction is necessary
2. **Define the API contract** — what endpoints will the extracted service expose
3. **Create the new repo** — scaffold a fresh Laravel app
4. **Copy domain code** — move `app/Core/<Domain>/` to the new app
5. **Copy migrations** — move `database/migrations/<domain>/` to new app
6. **Set up auth federation** — API tokens or shared session store
7. **Replace internal calls** — swap direct imports for HTTP/event calls
8. **Deploy independently** — new CI/CD pipeline
9. **Remove from monolith** — delete the extracted code from this repo
10. **Update governance docs** — reflect new ownership

---

## Current Extraction Readiness

| Domain | Ready? | Blockers |
|--------|:------:|----------|
| CondoFlow | ⚠️ Mostly | Shares `users` table with Core; would need user sync |
| Dynamic Forms | ⚠️ Mostly | Same user table dependency |
| Reference | N/A | Not a real domain — never extracted |
| Observability | ❌ No | Tightly coupled to Laravel's service container |

---

## Summary

```
TODAY:  Modular monolith with clean boundaries
        ↓
TRIGGER fires (compliance, scale, cadence, autonomy)
        ↓
EXTRACT the specific domain that needs isolation
        ↓
RESULT: Monolith + extracted service(s) communicating via API/events
```

We do not plan for extraction. We prepare for it by maintaining clean boundaries.
When the day comes, the boundaries are already drawn.
