# Core Platform — Identity/Auth Implementation Plan

## 1. Purpose

This document defines the planned implementation approach for the Identity/Auth module. It is **not code**, but a technical implementation guide that will drive future development. The plan is aligned with the existing architecture documents (`overview.md`, `endpoints.md`, `business-rules.md`, `flows.md`) and the frozen architectural decisions of Core Platform.

---

## 2. Implementation Scope

### Phase 1 — Included

- login (session + token)
- logout (session invalidation + token revocation)
- current authenticated user (`GET /auth/me`)
- password reset foundation (request + confirm)
- email verification foundation (verify + resend)
- Sanctum token issuance
- current token revocation
- platform admin access guard (Filament)
- basic auth tests (Pest)

### Phase 1 — Explicitly Excluded

- RBAC (roles, permissions, policies)
- tenant switching
- tenant membership management
- OAuth providers (Google, GitHub, etc.)
- SSO (SAML, OpenID Connect)
- Multi‑Factor Authentication (MFA)
- advanced session/device management (list active sessions, force logout)
- fine‑grained token scopes
- token expiration and rotation

---

## 3. Proposed Module Location

```
backend/app/Core/IdentityAuth/
```

**Suggested structure:**

```
IdentityAuth/
├── Actions/
├── DTOs/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Policies/
├── Services/
├── Tests/
├── Routes/
│   └── api.php
└── README.md
```

The exact structure may evolve, but it **must follow module ownership conventions** defined in `MODULE_TEMPLATE.md` and `CODING_STANDARDS.md`.

---

## 4. Core Actions

Actions contain application workflow logic, not controller glue.

| Action | Responsibility |
|---|---|
| `LoginUserAction` | Validate credentials and create a session-based login context |
| `LogoutUserAction` | Invalidate current session, revoke current token |
| `GetCurrentUserAction` | Return authenticated user identity |
| `IssueSanctumTokenAction` | Issue a Sanctum personal access token |
| `RevokeCurrentTokenAction` | Revoke the current Sanctum token |
| `RequestPasswordResetAction` | Send password reset notification |
| `ResetPasswordAction` | Validate reset token, update password |
| `VerifyEmailAction` | Validate signed verification link, mark email verified |
| `ResendEmailVerificationAction` | Resend verification notification |

---

## 5. DTOs

DTOs are used for validated input transfer between the HTTP layer and Actions.

| DTO | Purpose |
|---|---|
| `LoginData` | Email + password for login |
| `TokenIssueData` | Email + password for token issuance |
| `PasswordResetRequestData` | Email for forgot‑password request |
| `PasswordResetData` | Token + email + new password for reset |
| `EmailVerificationData` | Signed verification URL data |

---

## 6. HTTP Layer

### Controllers (thin)

| Controller | Endpoints |
|---|---|
| `AuthController` | `POST /auth/login`, `POST /auth/logout`, `GET /auth/me` |
| `PasswordController` | `POST /auth/forgot-password`, `POST /auth/reset-password` |
| `EmailVerificationController` | `GET|POST /auth/verify-email`, `POST /auth/resend-verification` |
| `TokenController` | `POST /auth/token`, `DELETE /auth/token/current` |

### Form Requests

| Request | Validates |
|---|---|
| `LoginRequest` | email, password |
| `TokenIssueRequest` | email, password |
| `PasswordResetRequest` | email |
| `PasswordResetConfirmRequest` | token, email, password |
| `EmailVerificationRequest` | signed URL parameters |

### API Resources

| Resource | Returns |
|---|---|
| `AuthenticatedUserResource` | ULID, name, email, verification status, platform admin flag |
| `TokenResource` | token string, token metadata, created timestamp |

---

## 7. Route Strategy

**Route file location:** `backend/app/Core/IdentityAuth/Routes/api.php`

**Conceptual endpoints:**

| Method | Path | Responsibility |
|---|---|---|
| POST | `/auth/login` | Session-based login |
| POST | `/auth/logout` | Logout (session + token) |
| GET | `/auth/me` | Current authenticated user |
| POST | `/auth/token` | Issue Sanctum token |
| DELETE | `/auth/token/current` | Revoke current token |
| POST | `/auth/forgot-password` | Request password reset |
| POST | `/auth/reset-password` | Confirm password reset |
| GET|POST | `/auth/verify-email` | Verify email |
| POST | `/auth/resend-verification` | Resend verification |

Route registration must be **explicit and module‑owned** (via `IdentityAuthServiceProvider`).

---

## 8. Session vs Token Boundary

- **Session login** supports web/admin flows (Filament, future browser‑based platform access).
- **Token issuance** supports API/integration flows (mobile, AI agents, external integrations).
- **Filament access remains session‑only.** Sanctum tokens do **not** grant access to the Filament admin panel.
- **Token behavior remains minimal** until the Authorization module defines formal scopes.

---

## 9. Platform Admin Boundary

- Platform admins live in the **global `users` table**.
- The `is_platform_admin` boolean flag controls initial Filament access.
- This flag is **operational**, not formal RBAC.
- Future Roles/Permissions may replace or extend this behavior.
- Platform admin authorization must remain **isolated from business authorization**.

---

## 10. Events

Future domain/application events that Identity/Auth should emit:

| Event | Trigger |
|---|---|
| `UserLoggedIn` | Successful login |
| `UserLoggedOut` | Logout |
| `LoginFailed` | Failed login attempt |
| `PasswordResetRequested` | Forgot‑password request |
| `PasswordChanged` | Password reset completed |
| `EmailVerified` | Email verification completed |
| `VerificationEmailResent` | Resend verification |
| `SanctumTokenIssued` | Token created |
| `SanctumTokenRevoked` | Token revoked |
| `PlatformAdminAccessed` | Platform admin login |
| `UnauthorizedPlatformAdminAccessAttempted` | Non‑admin attempts Filament access |

Event implementation may be **incremental**; not every event needs to be emitted from day one.

---

## 11. Audit Integration

- Identity/Auth should **emit events** that the Audit module can consume later.
- Audit persistence does **not** need to be implemented in Phase 1.
- Auth events should carry actor/context metadata (actor ID, IP address, user agent) when possible.

---

## 12. Notifications Integration

- Password reset and email verification depend on the Notifications module.
- Initial implementation may use **Laravel notification defaults** (mail, database).
- Future platform notifications should centralize through the Notifications module.

---

## 13. Testing Plan (Pest)

| Test | Validates |
|---|---|
| Successful login | Session created, token optionally returned |
| Failed login | Generic “Invalid credentials” response |
| Logout | Session invalidated, token revoked |
| Current user | Authenticated identity returned |
| Token issuance | Sanctum token created and returned |
| Token revocation | Token deleted, subsequent requests fail |
| Forgot password | Generic success response regardless of email existence |
| Reset password | Token validated, password updated |
| Email verification | Signed URL validated, `email_verified_at` set |
| Resend verification | Notification sent for unverified user |
| Platform admin can access Filament | `is_platform_admin = true` user can reach `/admin` |
| Non‑platform admin cannot access Filament | `is_platform_admin = false` user receives 403 |

Tests should validate **behavior and boundaries**, not framework internals.

---

## 14. Implementation Order

Recommended sequence:

1. Route registration foundation (`IdentityAuthServiceProvider`, `Routes/api.php`)
2. Form Requests + API Resources
3. DTOs
4. Actions (login, logout, current user first)
5. Controllers (thin wiring)
6. Token flows (issue + revoke)
7. Password reset + email verification flows
8. Platform admin access guard (Filament middleware/policy)
9. Events (emit basic events)
10. Tests (added alongside each step)

Tests may be added **alongside each step**, not deferred to the end.

---

## 15. Risks and Notes

- **Do not duplicate Laravel auth behavior unnecessarily.** Use Laravel’s built‑in authentication, password broker, and signed URL mechanisms.
- **Do not build RBAC inside Identity/Auth.** Authorization belongs to the Roles/Permissions module.
- **Do not mix tenant context into authentication.** Tenant resolution happens after authentication, inside the Tenancy module.
- **Avoid over‑customizing Sanctum too early.** Keep token abilities minimal until the Authorization module defines formal scopes.
- **Keep Filament session‑only.** Do not allow token‑based access to the admin panel.

---

## 16. Final Statement

This implementation plan enables Identity/Auth to be built incrementally while preserving module boundaries, authentication clarity, and future package extraction readiness. The module remains independent from authorization, tenancy, and business access control, ensuring that authentication infrastructure can be reused across all future domain applications without architectural coupling.

Authentication is infrastructure. Authorization is business logic. This separation is intentional and will be preserved throughout the platform’s evolution.
