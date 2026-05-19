# Identity/Auth — Known Issues and Intentional Limitations

This document records intentional limitations and deferred decisions in the Identity/Auth module. These are not bugs; they reflect deliberate Phase 1 scoping choices.

---

## Current Known Limitations

### Email Verification Access Mode

**Current behavior:** The verification route `GET /auth/verify-email/{id}/{hash}` requires the user to be authenticated (Bearer token or session) in addition to having a valid signed URL.

**Limitation:** Users who click the verification link from an email client without an active session or token will not be verified automatically.

**Future consideration:** A future onboarding flow may revisit this. Options include:
- Allowing public signed-link verification (remove authentication requirement)
- Redirecting unauthenticated users to login and then completing verification

This decision is intentionally deferred to when onboarding UX requirements are defined.

---

### Password Reset Does Not Invalidate Sessions or Tokens

**Current behavior:** After a successful password reset (`POST /auth/reset-password`), existing sessions and Sanctum tokens remain valid.

**Limitation:** A user whose password was changed (e.g., after account compromise) may still have active tokens that continue to work until they are manually revoked.

**Future work:** Session and token invalidation after a password change is planned as a future security hardening step. This is **not** Phase 1 scope.

---

### Audit Persistence Not Implemented

**Current behavior:** `RecordAuthAuditEvent` listener converts internal auth events into `AuthAuditEvent` payloads and passes them to `NullAuthAuditSink`, which discards them silently.

**Limitation:** No audit trail is written to the database. Auth events are generated and discarded.

**Future work:** A dedicated Audit module will implement `AuthAuditSink` and bind it in the container, replacing the null sink. Identity/Auth code requires no changes for this integration.

---

### Platform Admin Authorization Is Operational Only

**Current behavior:** Access to the Filament admin panel is gated by `is_platform_admin = true` on the `User` model.

**Limitation:** This is not a formal RBAC system. There are no roles, no permissions, and no per-resource access control within the Filament panel.

**Future work:** The Roles/Permissions module may define formal admin roles and replace or extend `canAccessPanel()` without changing the current contract.

---

### No MFA, OAuth, or SSO

**Current behavior:** Authentication is limited to email/password via session or Sanctum token.

**Limitation:** No multi-factor authentication, no OAuth providers (Google, GitHub, etc.), and no SSO (SAML, OpenID Connect) are available.

**Future work:** These capabilities are explicitly deferred and depend on product validation requirements. The architecture supports adding them through Sanctum and Laravel's authentication infrastructure without rewriting Identity/Auth.

---

## Not Bugs

The following behaviors are intentional and should not be filed as bugs:

### `POST /auth/login` Returns 419 in Postman Without CSRF Handling

The session login endpoint uses Laravel's CSRF middleware. Calling it from Postman without a valid CSRF token returns `419 CSRF Token Mismatch`. This is correct security behavior.

**Solution for Postman/API testing:** Use `POST /auth/token` to obtain a Bearer token instead. This is the recommended flow for all API, Postman, mobile, and programmatic clients.

### Filament Login Is Separate from API Token Login

Filament has its own login form at `/admin/login`. Logging in via `POST /auth/token` or `POST /auth/login` does not grant access to the Filament admin panel. Filament uses the `web` (session) guard exclusively.

### `POST /auth/forgot-password` Returns Success for Unknown Emails

The forgot-password endpoint always returns a generic success message regardless of whether the submitted email is registered. This is intentional to prevent email enumeration attacks.
