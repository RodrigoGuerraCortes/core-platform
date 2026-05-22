# DynamicForms — Submission Flow

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

Submission is a synchronous, transactional operation in V1. A submission either fully succeeds (record created, response returned) or fully fails (no record created, structured error returned). There are no partial submissions, no draft submissions, and no queued processing in V1.

---

## Pre-Conditions for a Valid Submission

Before validation even begins, the following must be true:

| Pre-condition | Error if false |
|---|---|
| Form exists and belongs to this tenant | `404 Not Found` |
| Form status is `active` | `410 Gone` (archived) or `403 Forbidden` (draft) |
| Form has an `active_version_id` set | `422` — form has no published version |
| User has `submit` permission on this form | `403 Forbidden` |

---

## Submission Flow (Step by Step)

```
POST /api/forms/{formId}/submissions
  │
  ├─ 1. Route model binding
  │     └─ Resolve Form by {formId}, scoped to current tenant
  │
  ├─ 2. Authorization
  │     └─ FormPolicy::submit() — user must have member/admin/owner role
  │
  ├─ 3. Load active FormVersion
  │     └─ $form->active_version_id must not be null
  │        Load FormVersion record (schema needed for validation)
  │
  ├─ 4. Form status check
  │     └─ If status !== 'active' → 410 or 403
  │
  ├─ 5. Multi-submission guard (if settings.allow_multiple_submissions = false)
  │     └─ Check if user already has a FormSubmission for this form
  │        → 409 if duplicate found
  │
  ├─ 6. Payload extraction
  │     └─ Read `payload` from request body
  │        Strip unknown top-level keys
  │
  ├─ 7. Schema validation (FormSubmissionValidator)
  │     └─ Validate payload against active version schema
  │        → 422 with field-level errors if invalid
  │
  ├─ 8. Create FormSubmission (DB transaction)
  │     └─ Insert record:
  │           id:               new ULID
  │           form_id:          $form->id
  │           form_version_id:  $form->active_version_id
  │           tenant_id:        current tenant
  │           submitted_by:     current user ID (or null)
  │           payload:          validated + stripped payload
  │           metadata:         { ip, user_agent, source: 'api' }
  │           submitted_at:     now()
  │
  └─ 9. Return 201 Created
        └─ FormSubmissionResource response
```

---

## Transactional Safety

Steps 8 (record creation) executes inside a database transaction:

```php
DB::transaction(function () use ($form, $version, $payload, $metadata) {
    FormSubmission::create([...]);
});
```

If the transaction fails (e.g., FK constraint violation, DB error), no partial record is created and the API returns `500 Internal Server Error` with a generic message. The full error is logged.

---

## Payload Handling Rules

1. **Strip unknown keys:** Keys in the submitted payload not present in the schema are removed silently before storage
2. **Section keys:** The schema renderer should not send section field keys. If they appear, they are stripped.
3. **File field keys:** In V1, file field keys are stripped from the payload (file upload not implemented)
4. **Type coercion:** The validator does not coerce types. If a field expects `number` and receives `"42"` (string), it is invalid. The frontend must submit correctly typed values.
5. **Null handling:** Optional fields not submitted are stored as `null` if their key is absent from the payload. The stored payload only contains keys that were present in the request.

---

## Payload Storage

The `payload` stored in `FormSubmission` is the **cleaned, validated payload** — not the raw request body. This means:

- Unknown keys are stripped
- No type coercion applied (values are stored exactly as submitted)
- Section keys are absent
- File keys are absent (V1)

The `schema` field on `FormVersion` is the permanent reference for interpreting the stored payload.

---

## Metadata Capture

Each submission captures minimal metadata for audit and debugging:

```json
{
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0 ...",
  "source": "api"
}
```

| Field | Source | Notes |
|---|---|---|
| `ip_address` | `$request->ip()` | Respects X-Forwarded-For if trusted proxies configured |
| `user_agent` | `$request->userAgent()` | May be null |
| `source` | hardcoded `"api"` in V1 | Future: `"web"`, `"embed"`, `"import"` |

IP addresses are personal data under GDPR. Data retention policy for `metadata` is deferred to a future platform-level concern.

---

## Response on Success

```json
HTTP 201 Created

{
  "data": {
    "id": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "form_id": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "form_version_id": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "submitted_by": "01JXXXXXXXXXXXXXXXXXXXXXX",
    "submitted_at": "2026-05-22T14:00:00.000000Z",
    "payload": {
      "full_name": "Jane Smith",
      "contact_email": "jane@example.com",
      "country": "ca"
    }
  }
}
```

The response includes the stored payload so the frontend can confirm what was saved.

---

## Response on Validation Failure

```json
HTTP 422 Unprocessable Entity

{
  "message": "The submitted data is invalid.",
  "errors": {
    "full_name": ["The Full Name field is required."],
    "country": ["The selected value is not a valid option."]
  }
}
```

Error keys map to field `key` values (not labels) so the frontend can display errors inline under the correct input.

---

## Idempotency

Submissions are NOT idempotent in V1. If a form allows multiple submissions, each `POST` creates a new `FormSubmission` record. There is no submission token or idempotency key mechanism.

**Risk:** Network retry on the client side may create duplicate submissions. Mitigation: disable the submit button after the first click (frontend convention — see async-ui-conventions.md). Full idempotency key support is deferred.

---

## What Happens When a Form Is Archived After Submission

- Existing `FormSubmission` records are unaffected
- The `FormVersion` record is unaffected
- The form's `status` changes to `archived`, `archived_at` is set
- New submissions are rejected with `410 Gone`
- Submissions remain readable via `GET /api/forms/{formId}/submissions` (admin only) — even after archival

---

## Async Processing (Future)

In V1, all submission processing is synchronous. Future operations that will require async processing:

- Email confirmation to submitter
- Webhook delivery to configured endpoints
- Notification to form owner on new submission
- File virus scanning (when `file` type is implemented)

When async is introduced, the synchronous flow above remains intact and a job is dispatched after the `FormSubmission` record is committed.
