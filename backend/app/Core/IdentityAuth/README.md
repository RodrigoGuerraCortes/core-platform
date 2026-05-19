# Identity/Auth Module

## Purpose

The Identity/Auth module is responsible for:

- user identities
- authentication
- sessions
- Sanctum tokens
- password recovery
- email verification
- identity lifecycle management

## Boundaries

- **Not responsible for**: RBAC, permissions, tenant authorization, business authorization rules.
- **Users are global platform identities**, not tenant-scoped records.
- **Tenant membership** is managed by the Tenancy module.
- **Authorization** is managed by the Roles/Permissions module.

## Internal Events

The following events are dispatched by the Identity/Auth module:

| Event | Dispatched when |
|---|---|
| `UserLoggedIn` | Session login succeeds |
| `UserLoggedOut` | Session logout occurs |
| `LoginFailed` | Session or token login fails due to invalid credentials |
| `SanctumTokenIssued` | A Sanctum API token is created successfully |
| `SanctumTokenRevoked` | The current Sanctum API token is revoked |
| `PasswordResetRequested` | A forgot-password request is received (always dispatched, regardless of whether the email exists) |
| `PasswordChanged` | A password reset completes successfully |
| `EmailVerified` | A user's email is newly marked as verified |
| `VerificationEmailResent` | A verification email is sent to an unverified user |

**Rules:**
- Events are **internal application events** — they are not exposed over HTTP.
- Events must **not** contain: raw passwords, plain-text API tokens, roles, permissions, or tenant data.
- **Audit persistence is not implemented yet.** The Audit module may consume these events in a future step via listeners.
- Events carry enough context (user, IP address, user agent where available) for future audit integration.

## Runtime Authentication Flows

### API Token Flow

Used for Postman, mobile apps, API integrations, and future AI integrations.

**Endpoints:**
- `POST /auth/token` — receives `email` + `password`, returns a Bearer token
- `GET /auth/me` — returns the authenticated user identity
- `DELETE /auth/token/current` — revokes the current token

**How it works:**
1. `POST /auth/token` with `{ "email": "...", "password": "..." }` returns `{ "data": { "token": "..." } }`
2. Include the token in subsequent requests: `Authorization: Bearer <token>`
3. This is the **recommended flow for Postman and API testing**

### Session Web Flow

Used for future browser-based web clients with server-controlled sessions.

**Endpoints:**
- `POST /auth/login` — receives `email` + `password`, creates a Laravel session
- `POST /auth/logout` — invalidates the current session and regenerates the CSRF token
- `GET /auth/me` — returns the authenticated user identity

**How it works:**
1. `POST /auth/login` with `{ "email": "...", "password": "..." }` creates a session and returns the user identity
2. The session is maintained via cookies on subsequent requests
3. `POST /auth/login` does **not** issue a Sanctum token
4. CSRF protection is active at runtime — direct Postman calls may return `419` unless CSRF cookies are handled

### Filament Admin Flow

Used for platform administration.

**Endpoint:** `/admin/login`

- Filament uses its own session-based login, independent of the API token and session flows above
- API Bearer tokens do **not** grant access to the Filament admin panel
- Filament admin access is currently controlled by the `is_platform_admin` flag on the `User` model

## Implementation Roadmap

### Completed

- [x] Module skeleton
- [x] Service provider registration
- [x] `GET /auth/me`
- [x] `POST /auth/token`
- [x] `DELETE /auth/token/current`
- [x] `POST /auth/login`
- [x] `POST /auth/logout`
- [x] Pest feature tests for current user, token auth, and session auth
- [x] Basic auth events

### Next

- [ ] Password reset foundation
- [ ] Email verification foundation
- [ ] Audit integration hooks
- [ ] Filament platform admin guard hardening
- [ ] Auth documentation final review

### Future / Out of Scope

- [ ] RBAC
- [ ] Tenant switching
- [ ] OAuth
- [ ] SSO
- [ ] MFA
- [ ] Device/session management UI
- [ ] Fine-grained token scopes

## Testing

Tests are located in `backend/tests/Feature/IdentityAuth/`.

| Test file | Coverage |
|---|---|
| `GetCurrentUserTest.php` | `GET /auth/me` — unauthenticated, authenticated, response shape |
| `TokenAuthenticationTest.php` | `POST /auth/token`, `DELETE /auth/token/current` — credentials, token lifecycle |
| `SessionAuthenticationTest.php` | `POST /auth/login`, `POST /auth/logout` — session lifecycle, token isolation |
