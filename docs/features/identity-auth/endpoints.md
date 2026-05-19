# Core Platform — Identity/Auth Endpoints Definition

## Implemented Endpoints

> Status: fully implemented as of 2026-05-19. All endpoints are covered by Pest feature tests.

### API / Token (Sanctum, stateless)

| Method | Path | Description | Auth required |
|---|---|---|---|
| `POST` | `/auth/token` | Issue a Sanctum Bearer token | No |
| `DELETE` | `/auth/token/current` | Revoke the currently active Bearer token | Bearer token |
| `GET` | `/auth/me` | Return the authenticated user identity | Bearer token or session |

> **Recommended for Postman, API clients, mobile apps, and AI integrations.** Use `POST /auth/token` to obtain a token, then pass it as `Authorization: Bearer <token>` on subsequent requests.

### Session (web, stateful)

| Method | Path | Description | Auth required |
|---|---|---|---|
| `POST` | `/auth/login` | Create a session-based login | No (but CSRF cookie required at runtime) |
| `POST` | `/auth/logout` | Invalidate the current session | Session |

> Session endpoints use Laravel's CSRF middleware. Direct calls from Postman without CSRF handling will return `419 CSRF Token Mismatch`. Use the token flow for API/Postman testing.

### Password Reset

| Method | Path | Description | Auth required |
|---|---|---|---|
| `POST` | `/auth/forgot-password` | Request a password reset email | No |
| `POST` | `/auth/reset-password` | Confirm reset with token and new password | No |

> `POST /auth/forgot-password` always returns a generic success response regardless of whether the email exists.

### Email Verification

| Method | Path | Description | Auth required |
|---|---|---|---|
| `GET` | `/auth/verify-email/{id}/{hash}` | Verify email via signed URL | Bearer token or session (+ `signed` middleware) |
| `POST` | `/auth/resend-verification` | Resend the verification email | Bearer token or session |

> The verification link is a signed, time-limited URL (60 minutes). The verification route requires both authentication and a valid signature.

### Filament Admin Panel

| Method | Path | Description |
|---|---|---|
| `GET/POST` | `/admin/login` | Filament session-based admin login |
| `GET` | `/admin` | Filament admin panel (requires `is_platform_admin = true`) |

> Filament uses session authentication exclusively. API Bearer tokens do **not** grant access to `/admin`. Non-admin users receive `403 Forbidden`. Unauthenticated users are redirected to `/admin/login`.

---

## 1. Authentication Strategy

The Identity/Auth module uses a **hybrid authentication strategy** that supports two authentication models simultaneously:

### Session Authentication

Session authentication is the primary mechanism for:

- Filament admin panel
- web platform access
- browser‑based interactions

Sessions are server‑controlled, secure, and provide a seamless experience for operational and administrative interfaces.

### Token Authentication

Token authentication is used for:

- APIs
- mobile applications
- future AI integrations
- external integrations

Laravel Sanctum supports both models simultaneously, allowing the platform to serve web and API clients from the same authentication infrastructure without architectural duplication.

---

## 2. Core Authentication Endpoints

The following conceptual endpoints define the authentication flows. They are **architecture‑oriented** and do not prescribe implementation payloads or route files.

### Login

```
POST /auth/login
```

**Responsibilities:**

- validate credentials (email + password)
- create an authenticated session
- optionally issue a Sanctum personal access token

### Logout

```
POST /auth/logout
```

**Responsibilities:**

- invalidate the current session
- revoke the current Sanctum token if applicable

### Current User

```
GET /auth/me
```

**Responsibilities:**

- return the authenticated identity
- return minimal authentication context (e.g., email, ULID, verification status)

### Forgot Password

```
POST /auth/forgot-password
```

**Responsibilities:**

- accept an email address
- send a password reset link via the Notifications module

### Reset Password

```
POST /auth/reset-password
```

**Responsibilities:**

- validate the reset token
- update the user’s password
- invalidate existing sessions/tokens (optional, future)

### Verify Email

```
GET|POST /auth/verify-email
```

**Responsibilities:**

- validate the email verification token
- mark the user’s email as verified

### Resend Verification

```
POST /auth/resend-verification
```

**Responsibilities:**

- resend the email verification notification to the authenticated user

---

## 3. Session Philosophy

- Sessions are **first‑class citizens** in the platform.
- Browser authentication uses secure, server‑controlled sessions.
- Admin access (Filament) relies exclusively on sessions.
- Sessions remain under server control; they are not exposed to client‑side manipulation.
- Session lifetime, rotation, and invalidation follow Laravel’s secure defaults.

**Preference:** sessions are the preferred authentication method for operational and administrative interfaces.

---

## 4. Token Philosophy

- Sanctum personal access tokens are the official token mechanism.
- Tokens are **API‑oriented** and intended for programmatic access.
- Tokens are **revocable** – a user or admin can invalidate a token at any time.
- The initial implementation is intentionally minimal:
  - no fine-grained token scoping initially; tokens use minimal default abilities until the Authorization module defines formal scopes
  - no token expiration (tokens are long‑lived until revoked)
- Future evolution may add:
  - token scopes (fine‑grained permissions per token)
  - token expiration (configurable TTL)
  - token rotation (refresh flows)

---

## 5. Security Philosophy

- **Secure‑by‑default:** passwords are hashed using Laravel’s default Laravel’s configured password hashing algorithm.
- **CSRF protection:** session‑based endpoints are protected by Laravel’s built‑in CSRF middleware.
- **Minimal auth surface:** only the authentication methods required for Phase 1 are exposed.
- **Gradual security expansion:** the architecture supports adding security features without rewriting the core.

**Future possibilities (not implemented in Phase 1):**

- Multi‑Factor Authentication (MFA)
- Device management (list/revoke active sessions)
- Suspicious login detection (geolocation, IP analysis)
- Token scopes and expiration
- Session management UI (view active sessions, force logout)

---

## 6. Error Response Philosophy

Authentication errors must follow a **consistent, predictable structure** regardless of the specific failure.

**Conceptual error categories:**

| Scenario | HTTP Status | Conceptual Behavior |
|---|---|---|
| Invalid credentials | 401 | Return a generic “Invalid credentials” message; do not reveal which field is incorrect. |
| Unauthenticated request | 401 | Return a standard “Unauthenticated” response. |
| Unverified account | 403 | Return a “Email not verified” message; include a hint to resend verification. |
| Expired reset token | 422 | Return a “Reset token expired” message; suggest requesting a new link. |
| Revoked token | 401 | Return a “Token revoked” message; the client must re‑authenticate. |

**Response consistency is more important than framework defaults.** All error responses should follow the envelope defined in `API_CONVENTIONS.md`:

```json
{
  "message": "Invalid credentials",
  "errors": {}
}
```

---

## 7. Relationship With Tenancy

- **Authentication is platform‑global.** A user authenticates against the single `users` table, not against a tenant.
- **Tenancy resolution happens AFTER authentication.** The authenticated user’s tenant context is resolved by the Tenancy module, not by Identity/Auth.
- **Tenant membership is not resolved by Identity/Auth.** The Identity module does not enforce tenant scoping; that responsibility belongs to the Authorization and Tenancy modules.

This separation is intentional and aligns with the platform’s architectural principles:

- Identity/Auth = *who you are*
- Tenancy = *which tenant you are acting on behalf of*
- Authorization = *what you are allowed to do*

---

## 8. Future Extensibility

The Identity/Auth module is designed to support future authentication methods without architectural rewrites.

**Planned future capabilities (not implemented in Phase 1):**

- OAuth providers (Google, GitHub, etc.)
- SSO (SAML, OpenID Connect)
- Multi‑Factor Authentication (TOTP, WebAuthn)
- Device session management (list/revoke active sessions)
- Tenant switching (a user belonging to multiple tenants)
- External identity federation (LDAP, Active Directory)

**Extensibility principle:** future authentication methods must integrate through the existing authentication infrastructure (Sanctum, sessions, policies) without requiring changes to the core authentication flow.

---

## 9. Final Statement

Identity/Auth provides stable authentication infrastructure for all future domain applications while remaining independent from authorization and tenancy concerns. The module is a foundational, reusable capability that every other module depends on, yet it does not dictate how those modules enforce their own access rules.

Authentication is infrastructure. Authorization is business logic. This separation is intentional and will be preserved throughout the platform’s evolution.
