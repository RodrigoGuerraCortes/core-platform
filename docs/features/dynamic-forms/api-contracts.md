# DynamicForms — API Contracts

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

All API endpoints are tenant-scoped, versioned under `/api/`, and follow the platform's REST conventions (see `docs/arquitecture/API_CONVENTIONS.md`). All responses use API Resources. All requests requiring a body use Form Request classes.

Authentication is required for all endpoints in V1 (Bearer token via Sanctum).

---

## Base URL Convention

```
/api/forms
/api/forms/{formId}/versions
/api/forms/{formId}/submissions
```

`{formId}` is always a ULID. Tenant scoping is enforced by the `TenantScope` Eloquent scope — no tenant segment in the URL.

---

## Form Endpoints

### `GET /api/forms`

List all forms for the current tenant.

**Authorization:** `FormPolicy::viewAny` — member/admin/owner

**Query parameters:**

| Parameter | Type | Description |
|---|---|---|
| `status` | string | Filter by `draft`, `active`, `archived` |
| `per_page` | integer | Results per page (default: 15, max: 100) |
| `page` | integer | Page number |

**Response: `200 OK`**

```json
{
  "data": [
    {
      "id": "01JXXXXXXXXXXXXXXXXXXXXXX",
      "name": "Customer Onboarding",
      "description": "Collect initial customer details.",
      "status": "active",
      "active_version_id": "01JXXXXXXXXXXXXXXXXXXXXXX",
      "archived_at": null,
      "created_at": "2026-05-22T10:00:00.000000Z",
      "updated_at": "2026-05-22T10:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1, "from": 1, "to": 1 },
  "links": { "first": "...", "last": "...", "prev": null, "next": null }
}
```

---

### `POST /api/forms`

Create a new form. Status defaults to `draft`.

**Authorization:** `FormPolicy::create` — admin/owner

**Request body:**

```json
{
  "name": "Customer Onboarding",
  "description": "Collect initial customer details."
}
```

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255 |
| `description` | string | nullable, max:2000 |

**Response: `201 Created`**

```json
{
  "data": {
    "id": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "name": "Customer Onboarding",
    "description": "Collect initial customer details.",
    "status": "draft",
    "active_version_id": null,
    "archived_at": null,
    "created_at": "2026-05-22T10:00:00.000000Z",
    "updated_at": "2026-05-22T10:00:00.000000Z"
  }
}
```

---

### `GET /api/forms/{formId}`

Retrieve a single form.

**Authorization:** `FormPolicy::view` — member/admin/owner

**Response: `200 OK`** — same shape as list item, single `data` object

---

### `PATCH /api/forms/{formId}`

Update form metadata (name, description only). Cannot change `status` via this endpoint.

**Authorization:** `FormPolicy::update` — admin/owner  
**Blocked if:** form is archived

**Request body:**

```json
{
  "name": "Updated Form Name",
  "description": "Updated description."
}
```

**Response: `200 OK`** — updated form resource

---

### `POST /api/forms/{formId}/publish`

Publish the most recent draft version of the form. Sets `active_version_id` to the latest `FormVersion` and sets `status` to `active`.

**Authorization:** `FormPolicy::publish` — admin/owner  
**Pre-conditions:**
- Form must not be archived
- The form must have at least one `FormVersion` with at least one non-section field

**Request body:** none

**Response: `200 OK`** — updated form resource with `active_version_id` set and `status: "active"`

---

### `POST /api/forms/{formId}/unpublish`

Remove the active version from a published form. Sets `active_version_id` to `null`, sets `status` back to `draft`. The `FormVersion` record is not deleted.

**Authorization:** `FormPolicy::unpublish` — admin/owner  
**Blocked if:** form is archived

**Response: `200 OK`** — updated form resource

---

### `POST /api/forms/{formId}/archive`

Archive a form. Sets `status` to `archived`, sets `archived_at`. Irreversible.

**Authorization:** `FormPolicy::archive` — admin/owner

**Response: `200 OK`** — updated form resource with `status: "archived"`

---

## Form Version Endpoints

### `GET /api/forms/{formId}/versions`

List all versions of a form.

**Authorization:** `FormVersionPolicy::viewAny` — member/admin/owner

**Response: `200 OK`**

```json
{
  "data": [
    {
      "id": "01JXXXXXXXXXXXXXXXXXXXXXX",
      "form_id": "01JXXXXXXXXXXXXXXXXXXXXXX",
      "version_number": 2,
      "label": "Added phone field",
      "published_at": "2026-05-22T11:00:00.000000Z",
      "created_at": "2026-05-22T10:30:00.000000Z"
    }
  ]
}
```

Note: `schema` is **not** included in the list response (too large). Use the detail endpoint.

---

### `POST /api/forms/{formId}/versions`

Create a new form version (draft schema edit). Increments `version_number` automatically.

**Authorization:** `FormVersionPolicy::create` — admin/owner  
**Blocked if:** form is archived

**Request body:**

```json
{
  "schema": {
    "version": 1,
    "title": "Customer Onboarding",
    "description": null,
    "settings": { "allow_multiple_submissions": false },
    "fields": [
      {
        "key": "full_name",
        "type": "text",
        "label": "Full Name",
        "required": true,
        "order": 1,
        "validation": { "max_length": 100 }
      }
    ]
  },
  "label": "Initial version"
}
```

| Field | Type | Rules |
|---|---|---|
| `schema` | object | required — validated against schema contract |
| `label` | string | nullable, max:255 |

**Response: `201 Created`** — new `FormVersion` resource including full `schema`

---

### `GET /api/forms/{formId}/versions/{versionId}`

Retrieve a single version including full schema.

**Authorization:** `FormVersionPolicy::view` — member/admin/owner

**Response: `200 OK`**

```json
{
  "data": {
    "id": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "form_id": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "version_number": 1,
    "label": null,
    "schema": { ... },
    "published_at": null,
    "created_at": "2026-05-22T10:00:00.000000Z"
  }
}
```

---

## Submission Endpoints

### `POST /api/forms/{formId}/submissions`

Submit a completed form.

**Authorization:** `FormSubmissionPolicy::create` — member/admin/owner  
**Pre-conditions:** form must be `active`, must have `active_version_id`

**Request body:**

```json
{
  "payload": {
    "full_name": "Jane Smith",
    "contact_email": "jane@example.com",
    "country": "ca"
  }
}
```

| Field | Type | Rules |
|---|---|---|
| `payload` | object | required — validated against active version schema |

**Response: `201 Created`** — `FormSubmission` resource

**Error responses:**
- `410 Gone` — form is archived
- `409 Conflict` — duplicate submission (if not allowed)
- `422 Unprocessable Entity` — payload validation failed

---

### `GET /api/forms/{formId}/submissions`

List all submissions for a form.

**Authorization:** `FormSubmissionPolicy::viewAny` — admin/owner only  
(Members cannot list all submissions — they use the "my submissions" endpoint)

**Query parameters:**

| Parameter | Type | Description |
|---|---|---|
| `version_id` | string | Filter by form version |
| `per_page` | integer | Default: 15, max: 100 |
| `page` | integer | |

**Response: `200 OK`** — paginated list of `FormSubmission` resources

---

### `GET /api/forms/{formId}/submissions/{submissionId}`

Retrieve a single submission.

**Authorization:** `FormSubmissionPolicy::view`  
- admin/owner: any submission  
- member: only if `submitted_by === current user`

**Response: `200 OK`** — full `FormSubmission` resource including `payload`

---

## Error Response Shapes

All errors follow the platform standard:

```json
{
  "message": "Human-readable summary.",
  "errors": {
    "field_key": ["Error message 1.", "Error message 2."]
  }
}
```

`errors` is only present on `422` responses. Other error codes return `message` only.

---

## API Resource Summary

| Resource | Class |
|---|---|
| Form list item | `FormListResource` |
| Form detail | `FormResource` |
| Form version list item | `FormVersionListResource` (no schema) |
| Form version detail | `FormVersionResource` (with schema) |
| Form submission | `FormSubmissionResource` |
