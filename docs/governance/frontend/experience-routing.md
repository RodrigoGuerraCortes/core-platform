# Experience Routing — Governance

## Architecture

The **Experience Layer** decouples the core router from vertical-specific knowledge. Each vertical product (CondoFlow, MiniHIS, future ERP modules) registers an `ExperienceDefinition` — the router resolves route ownership dynamically.

```
src/app/experiences/
├── types.ts       # ExperienceDefinition, ResolvedExperience interfaces
├── registry.ts    # All registered experiences (single source of truth)
├── resolver.ts    # resolveExperience(to) — route ownership resolution
├── index.ts       # Barrel export
└── __tests__/
    └── resolver.test.ts
```

## How It Works

```
Route Hit → resolveExperience(to) → ExperienceDefinition | null
                                          ↓
                              Router Guard uses:
                              • guestEntryRoute (redirect guests)
                              • authenticatedEntryRoute (redirect auth users)
```

1. **Guest hits protected route** → Redirect to owning experience's `guestEntryRoute`
2. **Authenticated user hits guest-only route** → Redirect to owning experience's `authenticatedEntryRoute`
3. **Route not owned by any experience** → Platform core defaults (`/login`, `/home`)

## ExperienceDefinition Contract

```typescript
interface ExperienceDefinition {
  key: string                      // Unique identifier
  guestEntryRoute: string          // Login path for this experience
  authenticatedEntryRoute: string  // Landing page when already authenticated
  routePrefixes: string[]          // Path prefixes that this experience owns
  navigationScope: NavigationScope // 'tenant' | 'standalone' | 'hybrid'
  branding?: ExperienceBranding    // Optional visual customization
}
```

## Adding a New Vertical

1. Define the experience in `registry.ts`:

```typescript
export const hisExperience: ExperienceDefinition = {
  key: 'his',
  guestEntryRoute: '/his/login',
  authenticatedEntryRoute: '/t/:tenantSlug/his',
  routePrefixes: ['/his', '/t/:tenantSlug/his'],
  navigationScope: 'hybrid',
  branding: { label: 'MiniHIS', icon: 'mdi-hospital-building' },
}
```

2. Add it to the `experiences` array in `registry.ts`.
3. Create the module's routes and pages as usual.
4. Done — the router guard handles redirects automatically.

## Route Ownership Rules

| Route Pattern | Owner | Guard Behavior |
|---------------|-------|----------------|
| `/condoflow/*` | CondoFlow | Guest → `/condoflow/login` |
| `/t/:slug/condoflow/*` | CondoFlow | Guest → `/condoflow/login` |
| `/his/*` | MiniHIS | Guest → `/his/login` |
| `/t/:slug/his/*` | MiniHIS | Guest → `/his/login` |
| `/login` | Platform Core | Auth → `/home` |
| `/t/:slug/dashboard` | Platform Core | Guest → `/login` |

## Forbidden Patterns

These patterns are **strictly prohibited** in the router and guards:

```typescript
// ❌ FORBIDDEN — hardcoded path checks
if (to.path.startsWith('/condoflow')) { ... }
if (to.path.includes('/his')) { ... }

// ❌ FORBIDDEN — direct vertical references in router core
return { name: 'condoflow.login' }

// ❌ FORBIDDEN — duplicated auth logic per vertical
if (isCondoflow) { redirectToCondoflowLogin() }
if (isHis) { redirectToHisLogin() }
```

**Correct approach:**

```typescript
// ✅ CORRECT — experience-aware resolution
const resolved = resolveExperience(to)
if (resolved) {
  return { path: getGuestEntryRoute(resolved.experience) }
}
```

## Guest vs Authenticated Routes

| Meta Flag | Meaning | Experience Redirect |
|-----------|---------|---------------------|
| `requiresAuth: true` | Must be logged in | → `experience.guestEntryRoute` |
| `guestOnly: true` | Must NOT be logged in | → `experience.authenticatedEntryRoute` |
| Neither | Open to all | No redirect |

## Independent Login Flows

Each experience can expose its own login page:

- `/login` — Platform core login
- `/condoflow/login` — CondoFlow resident login
- `/his/login` — MiniHIS staff login

All use the same Sanctum session auth APIs. The difference is:
- Branding and copy
- Post-login redirect destination
- Route guard ownership

## Testing

```bash
npx vitest run src/app/experiences/
```

Tests cover:
- `resolveExperience()` for all registered prefixes
- Platform core routes return `null`
- Guest entry route generation
- Authenticated entry route with tenant slug substitution
- Unknown routes

## Related

- [Experience Isolation](./experience-isolation.md) — navigation, branding, shell ownership

