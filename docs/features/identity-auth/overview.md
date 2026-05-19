# Core Platform — Identity/Auth Module Overview

## 1. Purpose

The Identity/Auth module is responsible for:

- user identities
- authentication
- sessions
- Sanctum tokens
- password recovery
- email verification
- identity lifecycle management

The module is **NOT** responsible for:

- RBAC
- permissions
- tenant authorization
- business authorization rules

---

## 2. Strategic Philosophy

- **Identity is global** – users are unique across the entire platform.
- **Users may belong to multiple tenants** in the future, but the identity itself remains platform‑wide.
- **Authentication is separated from authorization** – the module handles *who you are*, not *what you can do*.
- **Authentication is infrastructure** – it is a reusable capability consumed by all domain applications.
- **Business permissions belong elsewhere** – roles, permissions, and tenant‑scoped access control are owned by the Authorization and Tenancy modules.

**Identity/Auth ≠ Roles/Permissions**

---

## 3. Current Scope

Initial authentication strategy:

- email/password
- Laravel Sanctum
- session‑based authentication
- API token support

Included in Phase 1:

- login
- logout
- password reset
- email verification
- session management
- token issuance

Explicitly excluded from Phase 1:

- OAuth
- SSO
- LDAP
- social login
- external identity providers
- MFA

---

## 4. Relationship With Other Modules

### Tenancy

- Users are global identities.
- Tenant membership (which tenants a user belongs to) is handled by the Tenancy module.
- The Identity module does not enforce tenant scoping.

### Roles/Permissions

- Authorization (roles, permissions, policies) lives outside the Identity module.
- RBAC is a separate concern owned by the Authorization module.

### Audit

- Authentication events (login, logout, token creation, password reset, etc.) are dispatched as internal events.
- An audit hook boundary (`AuthAuditSink` / `AuthAuditPayloadFactory`) is implemented. The current sink is a no-op; the Audit module will own persistence.
- Audit payloads must never include passwords, raw tokens, roles, permissions, or tenant context.

### Notifications

- Email verification and password recovery flows rely on the Notifications module.
- Operational notifications (e.g., “your password was changed”) are also routed through Notifications.

---

## 5. User Model Philosophy

- **Single global `users` table** – all platform users reside in one table.
- **Unique emails globally** – email uniqueness is enforced at the database level.
- **Future tenant membership** – a user may belong to zero, one, or many tenants; this relationship is managed by the Tenancy module, not by Identity.
- **Future platform admin support** – a boolean flag (`is_platform_admin`) exists on the users table but is not part of the Identity module’s core responsibility.

**Users are platform‑level identities, not tenant‑scoped records.**

---

## 6. Security Philosophy

- **Secure‑by‑default** – passwords are hashed, tokens are scoped, sessions are managed.
- **Minimal initial auth surface** – only the authentication methods required for Phase 1 are exposed.
- **Avoid premature auth complexity** – OAuth, SSO, MFA, and external providers are intentionally postponed.
- **Gradual extensibility** – the architecture supports adding new authentication methods without rewriting the core.
- **Operational simplicity first** – the module should be easy to operate, debug, and audit with a two‑person team.

---

## 7. Future Evolution

The following capabilities are **planned but intentionally postponed**:

- OAuth providers (Google, GitHub, etc.)
- SSO (SAML, OpenID Connect)
- MFA (TOTP, WebAuthn)
- Tenant switching (a user belonging to multiple tenants)
- Organization memberships (nested tenant structures)
- External identity federation (LDAP, Active Directory)

**Future extensibility is documented but will not be implemented until product validation justifies the investment.**

---

## 8. Final Statement

Identity/Auth provides stable identity infrastructure for all future domain applications while remaining isolated from business authorization concerns. It is a foundational, reusable module that every other module depends on, yet it does not dictate how those modules enforce their own access rules.

---

## 9. Packaging Boundaries

The Identity/Auth module is designed to remain reusable across multiple domain applications.

The module should avoid:
- business-specific rules
- tenant-specific logic
- application-specific authorization behavior
- domain coupling

The module is intentionally being designed as future package-extraction candidate.

Current implementation remains inside the modular monolith,
but boundaries should remain extraction-ready.

Future extraction must not require major architectural redesign.
