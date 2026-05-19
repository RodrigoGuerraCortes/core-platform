# Worklog — 2026-05-19: Identity/Auth Foundation

## Summary

The Identity/Auth module was fully implemented from scratch across 9 controlled code slices. The module provides authentication infrastructure for all future domain applications on the Core Platform while remaining decoupled from authorization, tenancy, and business access control.

---

## Implemented

### Endpoints

| Method | Path | Notes |
|---|---|---|
| `POST` | `/auth/token` | Issues a Sanctum Bearer token |
| `DELETE` | `/auth/token/current` | Revokes the current Bearer token |
| `GET` | `/auth/me` | Returns authenticated user identity |
| `POST` | `/auth/login` | Session-based login (CSRF-protected) |
| `POST` | `/auth/logout` | Invalidates session and regenerates CSRF token |
| `POST` | `/auth/forgot-password` | Sends password reset email (always returns generic success) |
| `POST` | `/auth/reset-password` | Validates reset token and updates password |
| `GET` | `/auth/verify-email/{id}/{hash}` | Verifies email via signed URL |
| `POST` | `/auth/resend-verification` | Resends email verification notification |
| `GET/POST` | `/admin/login` | Filament admin session login |

### Internal Events

All 9 events are dispatched at the correct points in the auth lifecycle:

- `UserLoggedIn` — session login success
- `UserLoggedOut` — session logout
- `LoginFailed` — failed session or token login
- `SanctumTokenIssued` — token created
- `SanctumTokenRevoked` — token revoked
- `PasswordResetRequested` — forgot-password request received
- `PasswordChanged` — password reset completed
- `EmailVerified` — email marked as verified
- `VerificationEmailResent` — verification notification sent

### Audit Hooks

- `AuthAuditEvent` — immutable value object (safe, structured audit payload)
- `AuthAuditSink` — interface for future audit persistence
- `NullAuthAuditSink` — default no-op implementation
- `AuthAuditPayloadFactory` — maps all 9 events to audit-safe `AuthAuditEvent` objects
- `RecordAuthAuditEvent` — listener registered for all 9 events via `IdentityAuthServiceProvider`

### Filament Platform Admin Guard

- `User` model implements `Filament\Models\Contracts\FilamentUser`
- `canAccessPanel(Panel $panel): bool` returns `$this->is_platform_admin === true`
- Non-admin authenticated users receive `403 Forbidden`
- API Bearer tokens do not grant Filament access (Filament uses the `web` guard)

### Tests

8 Pest feature test files covering all endpoints, events, audit hooks, and the Filament access boundary:

- `GetCurrentUserTest.php`
- `TokenAuthenticationTest.php`
- `SessionAuthenticationTest.php`
- `PasswordResetTest.php`
- `EmailVerificationTest.php`
- `AuthEventsTest.php`
- `AuthAuditHooksTest.php`
- `FilamentPlatformAdminAccessTest.php`

### Documentation Updates

- `backend/app/Core/IdentityAuth/README.md` — complete module reference
- `docs/features/identity-auth/implementation-plan.md` — implementation status checklist and remaining work
- `docs/features/identity-auth/endpoints.md` — implemented endpoint reference table
- `docs/features/identity-auth/flows.md` — updated flows to reflect actual behavior (Filament guard, audit hooks, password reset, email verification)
- `docs/features/identity-auth/business-rules.md` — audit payload rules, Filament access rule
- `docs/features/identity-auth/overview.md` — updated Audit relationship
- `docs/features/identity-auth/changelog.md` — created (this session)
- `docs/features/identity-auth/known-issues.md` — created (this session)

---

## Validation

```bash
docker compose exec app php artisan test
```

**Result:** 52 tests passed, 164 assertions — full suite green.

---

## Notes

- **API/Postman flow:** use `POST /auth/token` to obtain a Bearer token. Do not use `POST /auth/login` for API testing — it requires a CSRF cookie and returns `419` without it.
- **Session login** (`POST /auth/login`) is for browser-based clients with CSRF cookie handling. It does **not** issue a Sanctum token.
- **Audit persistence** is deliberately not implemented. The `NullAuthAuditSink` discards all payloads. A future Audit module owns persistence and can replace the sink binding without touching Identity/Auth.
- **Roles/Permissions** and **Tenancy** remain separate modules. Identity/Auth does not resolve roles, permissions, or tenant context at any point.
- **`is_platform_admin`** is operational bootstrap authorization, not formal RBAC. Future Roles/Permissions may replace or extend `canAccessPanel()`.
