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
2. Filament presents the login form at `/admin/login`.
3. User submits email and password.
4. Credentials are validated against the `users` table.
5. A server‑controlled session is created.
6. Filament calls `canAccessPanel()` on the User model.
7. If `is_platform_admin = false`, the request is rejected with `403 Forbidden`.
8. If `is_platform_admin = true`, the user is admitted to the Filament dashboard.

**Implemented access boundary:**

- The `User` model implements `Filament\Models\Contracts\FilamentUser`.
- `canAccessPanel(Panel $panel): bool` returns `$this->is_platform_admin === true`.
- Unauthenticated requests are redirected to `/admin/login`.
- Authenticated users with `is_platform_admin = false` receive `403 Forbidden`.
- API Bearer tokens do not grant access; Filament uses the `web` guard exclusively.

**Clarifications:**

- The `is_platform_admin` flag is an operational indicator, not a business RBAC role.
- Later, formal authorization (Roles/Permissions) may replace or extend the `is_platform_admin` flag without changing the contract.
- Platform admin actions must eventually be auditable (login, logout, failed attempts).

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
5. A `PasswordChanged` event is dispatched (auditable via the audit hook boundary).

**Clarifications:**

- The password reset flow does **not** automatically log the user in after a successful reset.
- Session and token invalidation after password change are **future security hardening** (not implemented in Phase 1).
- The reset token is never included in any audit payload.

---

## 10. Email Verification Flow

**Implemented route:** `GET /auth/verify-email/{id}/{hash}`

**Flow:**

1. When a user registers or requests resend, a signed URL is generated using `URL::temporarySignedRoute('auth.email.verify', now()->addMinutes(60), ['id' => ..., 'hash' => sha1(email)])`.
2. The link is delivered to the user via the `VerifyEmail` notification.
3. The user opens the link (GET request).
4. The route validates: authenticated session/token + valid signature + non-expired link.
5. `email_verified_at` is set to the current timestamp.
6. An `EmailVerified` event is dispatched.

**Clarifications:**

- The verification route currently requires the user to be authenticated (Bearer token or session). Future onboarding flows may revisit this if public signed-link verification is needed.
- The verification link is valid for 60 minutes.
- The hash is `sha1($user->email)` — it validates that the link was issued for the correct email.

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

## 14. Audit Hooks Flow

Identity/Auth exposes an audit boundary between internal events and future audit persistence.

**Flow:**

1. An auth action (login, logout, password reset, etc.) completes.
2. The action or controller dispatches an internal auth event (e.g., `UserLoggedIn`, `LoginFailed`).
3. `RecordAuthAuditEvent` listener receives the event.
4. `AuthAuditPayloadFactory::fromEvent()` translates the event into an `AuthAuditEvent` value object.
5. The `AuthAuditEvent` is passed to the registered `AuthAuditSink`.
6. **The current sink is `NullAuthAuditSink` — a deliberate no-op.** Nothing is written to the database.

**Payload safety rules — payloads must never contain:**
- Raw passwords or password hashes
- Plain-text reset tokens or API tokens
- Roles, permissions, or tenant context
- Request body or authorization header content

**Payload fields allowed:** event name, actor/subject user ID, email address, token label (name), IP address, user agent, timestamp.

**Future integration:** a future Audit module may implement `AuthAuditSink` and replace the null sink in the container without modifying Identity/Auth code.

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
