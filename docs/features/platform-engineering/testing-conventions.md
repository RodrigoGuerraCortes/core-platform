# Testing Conventions

**Block:** 6 — Platform Engineering & Modular Expansion  
**Status:** Frozen  
**Date:** 2026-05-20

---

## Overview

The platform uses **Pest PHP** as the testing framework. Tests are the primary mechanism for proving architecture invariants, tenant isolation, and authorization correctness. Every module must ship tests alongside its implementation.

---

## Test Directory Structure

```
backend/tests/
├── Pest.php                          # Global configuration: Feature → TestCase + RefreshDatabase
├── TestCase.php                      # Base test case
├── Feature/
│   ├── {Module}/
│   │   └── {Model}ApiTest.php       # HTTP API tests for the module
│   ├── Tenancy/
│   │   ├── TenancyMiddlewareTest.php
│   │   ├── TenantScopeTest.php
│   │   ├── TenantCacheTest.php
│   │   ├── TenantLoggerTest.php
│   │   ├── TenantQueuePropagationTest.php
│   │   └── TenantRouteBindingConventionTest.php
│   └── MembershipResolverTest.php
└── Unit/
    ├── TenantContextTest.php
    └── ExampleTest.php
```

---

## Feature vs Unit

| Aspect | Feature (`tests/Feature/`) | Unit (`tests/Unit/`) |
|---|---|---|
| Database | ✓ `RefreshDatabase` (auto via `Pest.php`) | ✗ No database |
| HTTP | ✓ Full request/response cycle | ✗ No HTTP |
| Container | ✓ Full Laravel application | ✗ Minimal or pure PHP |
| Factories | ✓ Available | ✗ Not available |
| Use for | API behavior, tenant isolation, authorization, integration | Pure logic, value objects, stateless helpers |

**`Pest.php` applies `RefreshDatabase` to all tests in `Feature/` automatically.** Unit tests must declare their own setup — they do not receive `TestCase` or `RefreshDatabase` unless explicitly added.

```php
// tests/Pest.php
pest()->extend(Tests\TestCase::class)->use(RefreshDatabase::class)->in('Feature');
```

---

## Feature Test Requirements per Module

Every module that exposes HTTP routes must have a `{Model}ApiTest.php` in `tests/Feature/{Module}/`. The test file must cover all five test groups:

### 1. Tenant Isolation

Every tenant-owned resource must prove that Tenant A cannot access Tenant B resources.

```php
it('tenant A cannot retrieve tenant B project', function (): void {
    [$tenantA, $userA] = createTenantWithOwner();
    [$tenantB, ] = createTenantWithOwner();
    $projectB = Project::factory()->for($tenantB)->create();

    actingAsTenant($userA, $tenantA)
        ->getJson("/projects/{$projectB->id}")
        ->assertNotFound();
});
```

Required isolation tests for each model:
- [ ] Listing: Tenant A's list does not include Tenant B's records
- [ ] Retrieve single: Tenant A cannot retrieve Tenant B record by ID → 404
- [ ] Update: Tenant A cannot update Tenant B record → 404
- [ ] Delete: Tenant A cannot delete Tenant B record → 404

### 2. Authorization by Role

Every policy must be tested against all membership roles.

| Role | Must test |
|---|---|
| `owner` | All write actions permitted |
| `admin` | All write actions permitted |
| `member` | Read-only; write actions return 403 |

### 3. Runtime Invariants

Every tenant-aware endpoint must test:

| Scenario | Expected |
|---|---|
| Missing `X-Tenant-Id` header | `400` |
| Unauthenticated request | `401` |
| `tenant_id` not accepted from user input | `tenant_id` in body is ignored; value comes from resolved context |
| Platform admin has no special bypass | `403` if not a tenant member |

### 4. Enum Validation (if module uses enums)

- Valid enum values are accepted
- Invalid enum value returns `422`
- Enum value is serialized as a string in API responses (not as a PHP enum object)
- Model-level default is applied when field is omitted on create

### 5. Pagination (if endpoint is paginated)

- Response contains `data`, `links`, and `meta` keys
- Pagination respects tenant isolation (no cross-tenant records bleed between pages)
- Default page size is `15`

---

## Naming Conventions

| Type | Convention | Example |
|---|---|---|
| Test file | `{Concept}Test.php` | `ProjectApiTest.php`, `MembershipResolverTest.php` |
| Test description | `it('verb clause in present tense')` | `it('returns only projects for the authenticated tenant')` |
| `describe` block (optional) | Noun or behavior group | `describe('authorization', ...)` |

Test descriptions must be human-readable and describe the observable behavior, not the implementation.

**Good:** `it('tenant A cannot retrieve tenant B project')`  
**Bad:** `it('returns 404 when TenantScope filters by tenantId')`

---

## Test Helpers

Define reusable helper functions in `tests/Pest.php` or a dedicated file loaded by Pest. Helpers must be pure functions — no global state.

### Current Helpers

```php
// Creates a tenant + owner user, attaches them with role 'owner'
function createTenantWithOwner(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'owner']);
    return [$tenant, $user];
}

// Makes an authenticated request with X-Tenant-Id header
function actingAsTenant(User $user, Tenant $tenant): \Illuminate\Testing\TestResponse
{
    return test()->actingAs($user)->withHeaders(['X-Tenant-Id' => $tenant->id]);
}
```

When a new module requires a shared helper used across more than two test files, add it to `Pest.php` or a dedicated `tests/Helpers/` file and document it here.

---

## Forbidden Patterns in Tests

| Pattern | Reason |
|---|---|
| `withoutGlobalScopes()` | Bypasses tenant isolation and defeats the test |
| `withoutGlobalScope(TenantScope::class)` in tests that verify isolation | The scope is the feature under test — don't disable it |
| `assertDatabaseHas()` without scoping to tenant | May accidentally match another tenant's records |
| `User::factory()->create()` without assigning to a tenant | Creates a user with no membership — tests using this user will fail 400/403 in unexpected places |
| Hardcoded IDs (`->getJson('/projects/1')`) | Fragile; use `$project->id` |
| Testing implementation details | Test observable HTTP behavior, not internal method calls |
| `Queue::fake()` inside tenant propagation tests | Prevents verifying that the job actually restores tenant context |

---

## Async / Queue Testing

Jobs that use `HasTenantContext` must be tested in `Feature/` with a real queue driver (sync) or dispatched and inspected via `Queue::assertPushed()`. Do not `Queue::fake()` in tests that assert on tenant context propagation.

```php
it('queued job restores tenant context', function (): void {
    // use 'sync' queue driver (set in phpunit.xml or via config)
    $tenant = Tenant::factory()->create();
    // ... dispatch job and assert side effects under the correct tenant
});
```

---

## What Must Be Tested for Every Tenant-Owned Module

Checklist (minimum):

- [ ] Tenant A list excludes Tenant B records
- [ ] Tenant A cannot retrieve Tenant B record by ID
- [ ] Tenant A cannot update Tenant B record
- [ ] Tenant A cannot delete Tenant B record
- [ ] Owner can create
- [ ] Owner can update
- [ ] Owner can delete
- [ ] Admin can create
- [ ] Admin can update
- [ ] Admin can delete
- [ ] Member can list
- [ ] Member can view
- [ ] Member cannot create (403)
- [ ] Member cannot update (403)
- [ ] Member cannot delete (403)
- [ ] Missing `X-Tenant-Id` → 400
- [ ] Unauthenticated → 401
- [ ] `tenant_id` not accepted from input
- [ ] Platform admin no bypass
- [ ] Enum validation (if applicable)
- [ ] Pagination structure (if applicable)

---

## References

- [module-conventions.md](module-conventions.md)
- [architecture-guardrails.md](architecture-guardrails.md)
- [Tenancy Testing Strategy](../tenancy/testing-strategy.md)
