# Core Platform — Identity/Auth Flows

## 1. Purpose

This document defines the operational authentication flows for the Identity/Auth module. These flows guide implementation, tests, and future API contracts. They are **technical, concise, and implementation‑aware** while remaining framework‑agnostic where possible.

---

## 2. Flow Principles

- **Authentication identifies the user only.** Successful login does not load roles, permissions, or tenant context.
- **Authorization is handled outside Identity/Auth.** RBAC, permissions, and tenant‑scoped access control belong to the Authorization and Tenancy modules.
- **Tenancy is resolved after authentication.** The authenticated user’s tenant context is determined by the Tenancy module, not by Identity/Auth.
- **Filament admin access uses sessions.** The admin panel relies on server‑controlled session authentication.
- **API access uses Sanctum tokens.** Programmatic clients authenticate via personal access tokens.
- **Flows must remain auditable.** Critical authentication events must eventually be logged.
- **Flows must avoid leaking sensitive information.** Error responses must be generic and must not reveal whether an email exists or which field is incorrect.

---

## 3. Platform Admin Login Flow

The Filament admin login flow is the primary mechanism for platform administrators.

**Flow:**

1. Platform admin opens `/admin`.
2. Filament presents the login form.
3. User submits email and password.
4. Credentials are validated against the `users` table.
5. A server‑controlled session is created.
6. User is redirected to the Filament dashboard.
7. Access should be restricted to users with `is_platform_admin = true`.

**Clarifications:**

- Filament uses session‑based authentication exclusively.
- The `is_platform_admin` flag is an operational indicator, not a business RBAC role.
- Later, formal authorization (Roles/Permissions) may replace or extend the `is_platform_admin` flag.
- Platform admin actions must eventually be auditable (login, logout, failed attempts).
- Platform admin login uses the same global `users` table; it is not a separate admin identity store.
- API tokens do not grant access to the Filament admin panel; Filament access remains session‑based.

---

## 4. Web Session Login Flow

This flow describes general session‑based login for future browser‑based platform access (non‑admin).

**Flow:**

1. User submits email and password.
2. Credentials are validated.
3. A server‑controlled session is created.
4. The user identity becomes available to the application.
5. Tenancy context is resolved separately by the Tenancy module.
6. Authorization (roles, permissions) is handled separately by the Authorization module.

**Clarifications:**

- This flow does **not** resolve tenant membership or business permissions.
- It is the foundation for any future web‑based domain application.

---

## 5. API Token Login Flow

This flow describes how a client obtains a Sanctum personal access token.

**Flow:**

1. Client submits email and password.
2. Credentials are validated.
3. Optional email verification check (unverified users may be denied token issuance).
4. A Sanctum personal access token is issued.
5. The token is returned to the client **once** (it cannot be retrieved later).
6. The token is used for future API requests via the `Authorization: Bearer` header.

**Clarifications:**

- Token creation must eventually be auditable (actor, timestamp, token name).
- No fine‑grained token scopes are implemented initially.
- Tokens use minimal default abilities until the Authorization module defines formal scopes.
- Token issuance may be restricted for unverified users.

---

## 6. Current User Flow

This flow returns the authenticated user’s identity.

**Endpoint concept:** `GET /auth/me`

**Flow:**

1. Request is authenticated by session or token.
2. System resolves the authenticated user.
3. System returns minimal identity context.

**Returned conceptual data may include:**

- user ID / ULID
- name
- email
- email verification status (`email_verified_at`)
- platform admin flag (`is_platform_admin`) if applicable

**Clarifications:**

- Do **not** return tenant permissions, roles, or business context from this flow.
- This endpoint is identity‑only.

---

## 7. Logout Flow

**For session authentication:**

1. User requests logout.
2. Current session is invalidated (destroyed on the server).
3. CSRF/session token is regenerated where applicable.

**For token authentication:**

1. Client requests logout.
2. Current Sanctum token is revoked (deleted from the database).
3. Future requests using that token fail with a 401 response.

**Clarifications:**

- Global logout (revoke all sessions/tokens) is future work.
- Revoke‑all‑devices functionality is not part of Phase 1.

---

## 8. Forgot Password Flow

**Flow:**

1. User submits their email address.
2. System always returns a generic success response (e.g., “If that email exists, a reset link has been sent.”).
3. If the email exists, a password reset notification is sent via the Notifications module.
4. A secure reset token is generated using Laravel’s built‑in password broker.

**Clarifications:**

- Never reveal whether the email exists in the response.
- The reset token is time‑limited and single‑use.

---

## 9. Reset Password Flow

**Flow:**

1. User submits reset token, email, and new password.
2. Token is validated using the framework password broker mechanism, including expiration and single‑use behavior.
3. Password is updated using Laravel’s configured hashing algorithm.
4. Reset token is consumed (cannot be reused).
5. A password‑changed event should eventually be auditable.

**Clarifications:**

- Session/token invalidation after password change is **future work** (not implemented in Phase 1).
- The password reset flow does not automatically log the user in.

---

## 10. Email Verification Flow

**Flow:**

1. System generates a signed verification link (using Laravel’s signed URL mechanism).
2. User opens the verification link.
3. Signature and expiration are validated.
4. `email_verified_at` is set to the current timestamp.
5. A verification event should eventually be auditable.

**Clarifications:**

- The final HTTP method may follow Laravel’s signed verification flow (typically a GET request).
- The verification link is time‑limited.

---

## 11. Resend Verification Flow

**Flow:**

1. Authenticated user requests verification resend.
2. System checks whether the email is already verified.
3. If not verified, a verification notification is sent.
4. Response remains generic and safe (does not reveal whether the email was already verified).

**Clarifications:**

- Rate limiting for resend requests is **future work** (not implemented in Phase 1).

---

## 12. Token Revocation Flow

**Flow:**

1. Authenticated user or platform admin requests token revocation.
2. System identifies the target token (by token ID or current token).
3. Token is revoked (deleted from the `personal_access_tokens` table).
4. Future requests using that token fail with a 401 response.

**Clarifications:**

- Users should eventually be able to manage their own tokens (list, revoke).
- Platform admins may revoke tokens for operational or security reasons.
- Token revocation must eventually be auditable.

---

## 13. Failure Flows

Common failure scenarios and their conceptual behavior:

| Scenario | HTTP Status | Behavior |
|---|---|---|
| Invalid credentials | 401 | Generic “Invalid credentials” message; do not reveal which field is incorrect. |
| Unauthenticated request | 401 | Standard “Unauthenticated” response. |
| Unverified email | 403 | “Email not verified” message; include a hint to resend verification. |
| Expired reset token | 422 | “Reset token expired” message; suggest requesting a new link. |
| Invalid verification link | 422 | “Invalid verification link” message. |
| Revoked token | 401 | “Token revoked” message; client must re‑authenticate. |
| Unauthorized platform admin access | 403 | “Unauthorized” message; user is not a platform admin. |

**Clarifications:**

- All error responses must follow the envelope defined in `API_CONVENTIONS.md`:

```json
{
  "message": "Invalid credentials",
  "errors": {}
}
```

- Sensitive internal details must never leak.

---

## 14. Audit Events

The following events should eventually emit audit records. Audit implementation may be incremental; not every event needs to be logged from day one.

- login succeeded
- login failed
- logout
- token created
- token revoked
- password reset requested
- password changed
- email verified
- verification resent
- platform admin login
- unauthorized admin access attempt

Audit entries should include at minimum: actor ID, event type, timestamp, and relevant context (e.g., IP address, user agent).

---

## 15. Out of Scope

The following capabilities are explicitly **outside** the Identity/Auth flows and will not be implemented in Phase 1:

- tenant switching
- RBAC enforcement
- permission resolution
- OAuth providers (Google, GitHub, etc.)
- SSO (SAML, OpenID Connect)
- LDAP / Active Directory integration
- Multi‑Factor Authentication (MFA)
- device/session management UI (list active sessions, force logout)
- advanced token scope management

These capabilities belong to the Authorization, Tenancy, or future modules.

---

## 16. Final Statement

Identity/Auth flows provide a stable authentication lifecycle for web, admin, and API clients while keeping tenancy and authorization concerns outside the module. The flows are designed to be auditable, secure, and consistent, forming the foundation for all future domain applications.

Authentication is infrastructure. Authorization is business logic. This separation is intentional and will be preserved throughout the platform’s evolution.
