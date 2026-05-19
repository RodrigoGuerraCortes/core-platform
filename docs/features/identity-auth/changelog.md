# Identity/Auth — Changelog

## 2026-05-19 — Identity/Auth Foundation Completed

The full Identity/Auth module was implemented across 9 controlled code slices (Steps 1–9).

### Added

- **Module skeleton** — `IdentityAuthServiceProvider`, module directory structure under `app/Core/IdentityAuth/`
- **Current user endpoint** — `GET /auth/me` returns minimal identity context (user ID, name, email, verification status, platform admin flag); no roles, permissions, or tenant data
- **API token flow** — `POST /auth/token` issues a Sanctum Bearer token; `DELETE /auth/token/current` revokes it
- **Session flow** — `POST /auth/login` creates a server-controlled session; `POST /auth/logout` invalidates it and regenerates the CSRF token
- **Password reset** — `POST /auth/forgot-password` triggers a reset email (always returns generic success); `POST /auth/reset-password` validates the token and updates the password
- **Email verification** — `GET /auth/verify-email/{id}/{hash}` validates a signed, time-limited URL; `POST /auth/resend-verification` resends the notification to unverified users
- **Internal auth events** — 9 events dispatched at all significant auth points: `UserLoggedIn`, `UserLoggedOut`, `LoginFailed`, `SanctumTokenIssued`, `SanctumTokenRevoked`, `PasswordResetRequested`, `PasswordChanged`, `EmailVerified`, `VerificationEmailResent`
- **Audit hooks** — `AuthAuditEvent` value object, `AuthAuditSink` interface, `NullAuthAuditSink` (no-op default), `AuthAuditPayloadFactory` (maps all 9 events to audit-safe payloads), `RecordAuthAuditEvent` listener registered for all 9 events
- **Filament platform admin guard** — `User` model implements `FilamentUser::canAccessPanel()`, returning `$this->is_platform_admin === true`; non-admins receive `403 Forbidden`; Bearer tokens are rejected
- **Pest tests** — 52 tests, 164 assertions across 8 test files covering all endpoints, events, audit hooks, and the Filament access boundary

### Security Boundaries Established

- No tenant context in identity responses (`GET /auth/me` returns only global identity fields)
- No roles or permissions in identity responses
- API Bearer tokens do not grant access to the Filament admin panel
- `POST /auth/forgot-password` never reveals whether an email address is registered
- Audit payloads never include raw passwords, reset tokens, plain API tokens, roles, permissions, or tenant context
- Login failure responses use a single generic message regardless of whether the email or password was wrong

### Validation

- Full test suite: **52 tests, 164 assertions, all passing**
- Run: `docker compose exec app php artisan test`
