# Core Platform — Testing Strategy

## Purpose

Define the official testing philosophy for Core Platform.

---

# Testing Philosophy

Testing is mandatory.

The platform prioritizes:

- maintainability
- confidence
- business correctness
- authorization correctness

---

# Official Stack

```txt
Pest
PHPUnit
```

---

# Testing Layers

## Unit Tests

Used for:
- pure business rules
- services
- utilities

## Integration Tests

Primary testing strategy.

Used for:
- actions
- queries
- policies
- orchestration

## End-to-End Tests

Used selectively for:
- critical flows
- onboarding
- auth
- payments

---

# Mandatory Coverage Areas

- authorization
- tenancy
- audit
- AI orchestration
- workers
- uploads
- notifications

---

# AI Testing

AI systems should support:

- prompt snapshots
- deterministic validation
- orchestration testing
- provider mocking

---

# Architecture Testing

The platform should validate:

- forbidden dependencies
- module boundaries
- architectural contracts

---

# Testing Goals

The strategy exists to maximize:

- confidence
- stability
- refactor safety
- AI-assisted development