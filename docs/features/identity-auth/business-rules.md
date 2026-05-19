# Core Platform — Identity/Auth Business Rules

## 1. Purpose

This document defines the business and operational rules that govern the Identity/Auth module. These rules guide implementation, testing, and future evolution of the module. They are **technical, concise, and implementation‑aware** while remaining framework‑agnostic where possible.

---

## 2. Global Identity Rules

- **Users are global platform identities.** A user record exists once in the single `users` table, regardless of how many tenants the user belongs to.
- **Emails must be globally unique.** Email uniqueness is enforced at the database level. No two users may share the same email address.
- **Users are not duplicated per tenant.** Tenant membership is managed by the Tenancy module, not by Identity/Auth.
- **Users may belong to multiple tenants in the future.** The Identity module does not enforce a one‑tenant‑per‑user constraint.
- **Tenant membership is outside this module.** The Identity module does not store or resolve which tenants a user belongs to.

---

## 3. Account Creation Rules

- **Users may be created by platform admins initially.** Self‑registration is not part of Phase 1 unless explicitly enabled later.
- **Bootstrap admin users may exist only for local/development/staging environments.** These users are created by seeders or setup scripts and must never appear in production.
- **Production default users are forbidden.** No hard‑coded or default user accounts may exist in production.

---

## 4. Platform Admin Rules

- **Platform admins are operational users.** They manage tenants, global settings, and platform‑level operations.
- **Platform admin status is not business RBAC.** The `is_platform_admin` flag is a bootstrap/operational indicator, not a replacement for the Roles/Permissions module.
- **Filament admin access requires `is_platform_admin = true`.** The User model implements `FilamentUser::canAccessPanel()`, which returns `$this->is_platform_admin === true`. Users with `is_platform_admin = false` receive `403 Forbidden`.
- **API Bearer tokens do not grant Filament access.** Filament uses the `web` (session) guard exclusively.
- **Platform admin access is for platform operations only.** It does not grant automatic access to tenant‑scoped business resources.
- **Platform admin behavior must be auditable.** Every action performed by a platform admin must be traceable.
- **`is_platform_admin` is a bootstrap/operational flag.** Future Roles/Permissions may replace or extend this check without changing the `canAccessPanel()` contract.

---

## 5. Authentication Rules

- **Email/password is the initial authentication method.** No OAuth, SSO, or social login in Phase 1.
- **Authentication must not resolve business permissions.** Successful login identifies the user only; it does not load roles, permissions, or tenant context.
- **Tenant context is resolved after authentication.** The authenticated user’s tenant is determined by the Tenancy module, not by Identity/Auth.
- **Failed login responses must not reveal whether the email or password was incorrect.** A generic “Invalid credentials” message is returned for any authentication failure.

---

## 6. Session Rules

- **Sessions are used for browser/admin interactions.** Filament and web platform access rely on server‑controlled sessions.
- **Sessions are server‑controlled.** They are not exposed to client‑side manipulation.
- **Logout invalidates the current session.** The session is destroyed on the server.
- **Session lifecycle follows secure Laravel defaults initially.** Session lifetime, rotation, and invalidation use Laravel’s built‑in configuration.
- **Advanced session management (list active sessions, force logout) is future work.**

---

## 7. Token Rules

- **Sanctum personal access tokens are used for API access.** Tokens are the official mechanism for programmatic authentication.
- **Tokens must be revocable.** A user or admin can invalidate a token at any time.
- **Token creation must be auditable.** Every token issuance is logged with actor, timestamp, and token name.
- **No fine‑grained token scoping initially.** Tokens use minimal default abilities until formal scopes are defined by the Authorization module.
- **Expiration and rotation are future work.** Tokens are long‑lived until revoked.

---

## 8. Password Rules

- **Passwords must be hashed using Laravel’s configured hashing algorithm.**
- **Password reset flows must use secure tokens.** Reset tokens are time‑limited and single‑use.
- **Password reset should not reveal whether an email exists.** A generic message is returned regardless of whether the email is registered.
- **Password changes should invalidate sessions/tokens in the future.** This is not implemented in Phase 1 but is a planned security improvement.
- **Password policy (minimum length, complexity) may be strengthened later.** The initial implementation uses Laravel’s default validation.

---

## 9. Email Verification Rules

- **Email verification is required before sensitive authenticated actions.** Unverified users may be restricted from sensitive authenticated operations, such as API token creation or administrative access.
- **Verification flow must be secure.** The verification link is signed and time‑limited.
- **Verification resend must be rate‑limited in the future.** Phase 1 may not enforce rate limiting, but the architecture supports it.
- **Final HTTP method may follow Laravel signed verification flow.** The verification endpoint uses a signed URL that is validated on the server.
- **Verification status belongs to the user identity.** The `email_verified_at` column is part of the `users` table and is managed by Identity/Auth.

---

## 10. Error Handling Rules

- **Error responses must be consistent.** All authentication errors follow the envelope defined in `API_CONVENTIONS.md`.
- **Invalid credentials must be generic.** Do not reveal whether the email or password was incorrect.
- **Unauthenticated requests return 401.** A standard “Unauthenticated” response is returned.
- **Forbidden actions return 403.** For example, an unverified account attempting a restricted action.
- **Validation failures return 422.** Validation errors follow Laravel conventions.
- **Responses should follow `API_CONVENTIONS.md`.** The error structure is:

```json
{
  "message": "Invalid credentials",
  "errors": {}
}
```

---

## 11. Audit Rules

- **Audit hooks are implemented as an event‑to‑payload boundary.** Identity/Auth dispatches internal events; `RecordAuthAuditEvent` listener converts them into `AuthAuditEvent` payloads and passes them to the registered `AuthAuditSink`.
- **Audit persistence is not yet implemented.** The current `NullAuthAuditSink` is a deliberate no‑op. The Audit module will own persistence.
- **Audit payloads must never contain:** raw passwords or password hashes, plain‑text reset tokens, plain‑text API tokens, roles, permissions, tenant context, request body content, or authorization header content.
- **Audit payloads may contain:** event name, actor/subject user ID, attempted email address, token label (name), IP address, user agent, and timestamp.
- **All significant auth actions are already covered** by internal events: login, logout, login failure, token issuance, token revocation, password reset request, password change, email verification, and verification resend.

---

## 12. Out of Scope

The following capabilities are explicitly **outside** the Identity/Auth module and will not be implemented in Phase 1:

- RBAC (roles, permissions, policies)
- tenant roles (tenant‑scoped authorization)
- OAuth providers (Google, GitHub, etc.)
- SSO (SAML, OpenID Connect)
- LDAP / Active Directory integration
- social login (Facebook, Twitter, etc.)
- Multi‑Factor Authentication (MFA)
- advanced device management (list/revoke active sessions)
- tenant switching (a user belonging to multiple tenants)

These capabilities belong to the Authorization, Tenancy, or future modules.

---

## 13. Final Statement

Identity/Auth business rules protect global identity consistency while keeping authorization, tenancy, and business access control outside this module. The module is a foundational, reusable capability that every other module depends on, yet it does not dictate how those modules enforce their own access rules.

Authentication is infrastructure. Authorization is business logic. This separation is intentional and will be preserved throughout the platform’s evolution.
