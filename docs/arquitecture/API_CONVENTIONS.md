# Core Platform — API Conventions

## 1. Purpose

This document defines the official API conventions for:

- backend development
- frontend integration
- AI‑assisted development
- third‑party integrations
- future API evolution

All APIs should follow these conventions unless explicitly justified otherwise.

---

## 2. API Philosophy

- consistency over custom patterns
- explicit contracts over implicit behavior
- stable response shapes
- tenant‑aware by default
- frontend‑friendly APIs
- AI‑friendly API structures
- pragmatic REST‑first design

---

## 3. API Versioning

Official standard:

```
/api/v1/
```

- All public APIs must be versioned.
- Breaking changes require new versions.
- Internal consistency is prioritized.

---

## 4. Authentication Standard

Official authentication strategy:

**Laravel Sanctum**

- Authenticated APIs must require Sanctum authentication.
- Authorization must remain policy‑based.
- Authentication must remain tenant‑aware.

---

## 5. Tenant Resolution Strategy

Phase 1 official strategy:

**X‑Tenant‑Id header**

- Requests must remain tenant‑aware.
- Tenant isolation is mandatory.
- Future subdomain/hybrid strategies may evolve later.

---

## 6. Response Envelope Standard

Official response structure:

**Success example:**

```json
{
  "data": {},
  "meta": {}
}
```

**Collection example:**

```json
{
  "data": [],
  "meta": {
    "pagination": {}
  }
}
```

- APIs should maintain predictable envelopes.
- `meta` should contain pagination and additional metadata.
- Response shapes should remain stable.

---

## 7. Error Response Standard

Official error structure example:

```json
{
  "message": "Validation failed",
  "errors": {
    "email": [
      "Invalid email"
    ]
  }
}
```

- Validation errors should follow Laravel conventions.
- APIs should return predictable error shapes.
- Sensitive internal details must never leak.

---

## 8. UUID Standard

UUIDs are the official public identifier strategy.

- Public APIs should expose UUIDs.
- Internal numeric IDs should remain hidden.
- Identifiers should remain opaque externally.

---

## 9. Pagination Standard

Official pagination conventions:

```
?page=
&per_page=
```

- Laravel pagination conventions should be preferred.
- Collection endpoints should remain predictable.

---

## 10. Filtering Standard

Official filtering example:

```
?filter[status]=active
```

- Filtering must remain explicit.
- Filter behavior should remain predictable.
- Avoid custom query conventions per endpoint.

---

## 11. Sorting Standard

Official sorting example:

```
?sort=-created_at
```

- Minus prefix indicates descending order.
- Sorting should remain consistent across modules.

---

## 12. Relationship Include Standard

Official include example:

```
?include=roles,permissions
```

- Relationship loading should remain explicit.
- Avoid hidden eager loading behavior.
- APIs should avoid unnecessary payload inflation.

---

## 13. Naming Convention Standard

Official conventions:

- **URLs:** kebab‑case
- **JSON fields:** camelCase

Examples:

```
/api/v1/tenant-users
```

```json
{
  "firstName": "Rodrigo"
}
```

- Naming consistency is mandatory.
- Avoid mixed conventions.

---

## 14. Resource Standard

Laravel API Resources are officially required.

- APIs should never expose raw models directly.
- Response formatting should remain centralized.
- Resources improve consistency and AI‑assisted development.

---

## 15. Soft Delete API Behavior

Official behavior:

Soft‑deleted entities should behave as non‑existent externally unless explicitly restored/admin‑accessed.

- Deleted entities should normally return 404.
- Restore workflows should remain explicit.

---

## 16. Async Operation Standard

Long‑running operations should support queued responses.

Official example:

```json
{
  "status": "queued",
  "jobId": "uuid"
}
```

Examples:

- AI execution
- uploads
- imports
- exports

---

## 17. AI Endpoint Standard

AI endpoints should remain isolated.

Official example:

```
/api/v1/ai/
```

- AI execution should remain explicit.
- AI APIs should follow the same response conventions.
- Orchestration should not leak implementation complexity.

---

## 18. Rate Limiting Standard

Phase 1 strategy:

**Laravel default rate limiting.**

- Advanced rate limiting is future evolution.
- APIs should remain operationally simple initially.

---

## 19. OpenAPI Standard

OpenAPI/Swagger documentation is officially encouraged.

- APIs should remain documentable.
- Frontend integration should remain predictable.
- AI‑assisted tooling benefits from explicit API contracts.

---

## 20. Security Rules

- Tenant isolation is mandatory.
- Authorization policies are mandatory.
- Internal implementation details must not leak.
- Validation is required.
- APIs must remain explicit and predictable.

---

## 21. AI‑Assisted Development Rules

- AI‑generated endpoints must follow these conventions.
- Generated APIs must remain reviewable.
- Consistency is mandatory.
- Generated APIs must not bypass authorization or tenancy rules.

---

## 22. Final Statement

Core Platform APIs are designed to remain explicit, consistent, tenant‑aware, AI‑friendly, and operationally predictable while supporting long‑term evolution.
