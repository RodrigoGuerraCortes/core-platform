# DynamicForms — Authorization Rules

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Authorization follows the platform's policy-based model. All access control is defined in Laravel Policies registered through `DynamicFormsServiceProvider`. No authorization logic appears in controllers, commands, or queries.

---

## Platform Roles

DynamicForms uses the tenant roles established by the platform:

| Role | Meaning |
|---|---|
| `owner` | Tenant owner — full control |
| `admin` | Tenant administrator — full control except destructive tenant ops |
| `member` | Standard tenant member — limited to consuming features |

There are no DynamicForms-specific roles in V1. All authorization is expressed as combinations of the three platform roles.

---

## Permission Matrix

### Form Management

| Action | owner | admin | member |
|---|---|---|---|
| Create a form | ✓ | ✓ | ✗ |
| View form list | ✓ | ✓ | ✓ |
| View form detail | ✓ | ✓ | ✓ |
| Update form metadata (name, description) | ✓ | ✓ | ✗ |
| Publish a form version | ✓ | ✓ | ✗ |
| Unpublish a form | ✓ | ✓ | ✗ |
| Archive a form | ✓ | ✓ | ✗ |
| Hard delete a form | ✗ | ✗ | ✗ |

Hard deletion is not permitted through the API for any role in V1.

### Form Version Management

| Action | owner | admin | member |
|---|---|---|---|
| Create a new version (draft edit) | ✓ | ✓ | ✗ |
| View version list | ✓ | ✓ | ✓ |
| View version detail (including schema) | ✓ | ✓ | ✓ |
| Delete a version | ✗ | ✗ | ✗ |

Versions are immutable — no update or delete is permitted for any role.

### Submissions

| Action | owner | admin | member |
|---|---|---|---|
| Submit a form | ✓ | ✓ | ✓ |
| View own submission | ✓ | ✓ | ✓ |
| View all submissions for a form | ✓ | ✓ | ✗ |
| Delete a submission | ✗ | ✗ | ✗ |

Members may only view their own submissions (`submitted_by = current user`). Admins and owners see all submissions for any form in the tenant.

Submissions cannot be deleted in V1.

---

## Policy Classes

```
App\Core\DynamicForms\Policies\FormPolicy
App\Core\DynamicForms\Policies\FormVersionPolicy
App\Core\DynamicForms\Policies\FormSubmissionPolicy
```

All policies extend nothing — they are plain PHP classes implementing the standard policy method signatures.

### `FormPolicy` Method Signatures

```php
public function viewAny(User $user): bool
public function view(User $user, Form $form): bool
public function create(User $user): bool
public function update(User $user, Form $form): bool
public function publish(User $user, Form $form): bool
public function unpublish(User $user, Form $form): bool
public function archive(User $user, Form $form): bool
public function delete(User $user, Form $form): bool   // always false
```

### `FormVersionPolicy` Method Signatures

```php
public function viewAny(User $user, Form $form): bool
public function view(User $user, FormVersion $version): bool
public function create(User $user, Form $form): bool
public function delete(User $user, FormVersion $version): bool  // always false
```

### `FormSubmissionPolicy` Method Signatures

```php
public function viewAny(User $user, Form $form): bool           // admin/owner: all; member: own only
public function view(User $user, FormSubmission $submission): bool
public function create(User $user, Form $form): bool
public function delete(User $user, FormSubmission $submission): bool  // always false
```

---

## Implementation Notes

### Tenant Scope as the First Guard

Tenant scoping (via `TenantScope` Eloquent scope) is the first line of defense. A policy method only receives a model that already belongs to the current tenant. There is no need to check `$form->tenant_id === $user->tenant_id` inside the policy — the model could not have been loaded otherwise.

This mirrors the platform convention: scope first, authorize second.

### Role Resolution

Policies resolve roles via the user's tenant membership:

```php
// Inside FormPolicy
private function isAdminOrOwner(User $user): bool
{
    return $user->tenantRole()->whereIn('role', ['owner', 'admin'])->exists();
}
```

The exact mechanism depends on the IdentityAuth module's role resolution contract. DynamicForms policies must not duplicate role storage or logic.

### Archived Form Policy

When a form is archived:
- `view` → still allowed (admin/owner/member can see archived forms)
- `update` → denied for all roles
- `publish` → denied for all roles
- `create` (new version) → denied for all roles
- `submit` → denied for all roles (`FormSubmissionPolicy::create` checks form status)

---

## Policy Registration

Policies are registered in `DynamicFormsServiceProvider` via the `$policies` map inherited from `CoreModuleServiceProvider`:

```php
protected array $policies = [
    Form::class           => FormPolicy::class,
    FormVersion::class    => FormVersionPolicy::class,
    FormSubmission::class => FormSubmissionPolicy::class,
];
```

---

## Authorization in Controllers / Commands

Controllers call `$this->authorize()` before delegating to a command. Commands do NOT re-authorize — authorization is a controller-layer concern only.

```php
// In FormSubmissionController
public function store(SubmitFormRequest $request, Form $form): JsonResponse
{
    $this->authorize('create', [FormSubmission::class, $form]);

    $submission = $this->commandBus->dispatch(
        new SubmitFormCommand(
            formId: $form->id,
            versionId: $form->active_version_id,
            payload: $request->payload(),
            submittedBy: $request->user()?->id,
        )
    );

    return FormSubmissionResource::make($submission)->response()->setStatusCode(201);
}
```

---

## Future Authorization Extensions

Not in V1 — documented here for future planning:

- **Per-form submission access control** — restrict who can submit a specific form (e.g., invited users only)
- **Form-level custom roles** — form creator may grant submit access to specific users
- **Public/unauthenticated submissions** — forms flagged as public bypass the authentication check entirely
- **Read-only API tokens** — external systems accessing submission data via scoped tokens

These require extensions to both the authorization layer and the route configuration. Do not implement prematurely.
