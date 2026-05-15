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

## Current Implementation

- `GET /auth/me` – returns the authenticated user identity (id, name, email, email_verified_at, is_platform_admin).

## Future Endpoints

- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/token`
- `DELETE /auth/token/current`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET|POST /auth/verify-email`
- `POST /auth/resend-verification`

## Testing

Tests are located in `backend/tests/Feature/IdentityAuth/`.
