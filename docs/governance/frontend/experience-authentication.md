# Experience Authentication — Governance

## Architecture

Authentication in the platform is split into two layers:

```
┌─────────────────────────────────────────────────┐
│           Shared Runtime Auth                    │
│  (Sanctum, session, CSRF, auth store, API)      │
└──────────────────────┬──────────────────────────┘
                       │ used by
        ┌──────────────┼──────────────────┐
        ▼              ▼                  ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  Platform    │ │  CondoFlow   │ │  MiniHIS     │
│  Auth Flow   │ │  Auth Flow   │ │  Auth Flow   │
│  /login      │ │  /condoflow/ │ │  /his/login  │
│              │ │  login       │ │              │
└──────────────┘ └──────────────┘ └──────────────┘
```

### Shared Runtime Auth

- **Sanctum SPA** session-based authentication
- **`/sanctum/csrf-cookie`** — CSRF token
- **`/api/auth/login`** — establish session
- **`/api/auth/logout`** — destroy session
- **`/api/auth/me`** — current user
- **`useAuthStore`** — Pinia store (user, isAuthenticated, login, logout, bootstrap)
- **Single Laravel backend** — one session, one cookie jar

### Experience Auth Flow

Each experience declares its own auth flow via `ExperienceAuth`:

```typescript
interface ExperienceAuth {
  loginRoute: string             // Where to send unauthenticated users
  authenticatedRedirect: string  // Where to send after login (supports :tenantSlug)
  logoutRedirect?: string        // Where to send after logout
}
```

## How Login Works

```
User hits /t/acme/condoflow/buildings (unauthenticated)
  → Router guard: resolveExperience(to) → condoflow
  → Redirect to condoflow.auth.loginRoute (/condoflow/login?redirect=/t/acme/condoflow/buildings)

User submits credentials on /condoflow/login
  → useExperienceAuth().login(email, password)
    → authStore.login() (shared Sanctum)
    → resolvePostLoginRedirect() → query.redirect || auth.authenticatedRedirect
  → router.push(redirectPath)
```

## Key Composable: `useExperienceAuth`

Location: `src/app/experiences/auth.ts`

```typescript
const { login, isLoading, branding, resolvePostLoginRedirect, resolvePostLogoutRedirect } = useExperienceAuth()
```

- Auto-resolves current experience from route
- Login pages NEVER hardcode redirect destinations
- Branding is experience-driven

## Auth Ownership Boundaries

| Concern | Owner | NOT Owner |
|---------|-------|-----------|
| Session management | Shared runtime | Experiences |
| CSRF handling | Shared runtime | Experiences |
| Login API calls | Shared runtime | Experiences |
| Login page UI/branding | Experience | Shared runtime |
| Post-login redirect | Experience (via auth config) | Router core |
| Post-logout redirect | Experience (via auth config) | Router core |
| Tenant resolution | Shared runtime + stores | Experiences |
| Role/permission checks | Future RBAC module | Auth layer |

## MSW Auth Simulation

Development mock users are experience-aware:

```typescript
// Switch experience in browser console:
localStorage.setItem('msw:experience', 'condoflow')  // or 'platform', 'condoflow_admin'

// Toggle logged-out state:
localStorage.setItem('msw:logged-out', 'true')
```

Available mock personas:

| Key | Experience | Role | Name |
|-----|-----------|------|------|
| `platform` | Platform | platform_admin | Dev User |
| `condoflow` | CondoFlow | resident | María Residenta |
| `condoflow_admin` | CondoFlow | building_admin | Admin Condo |

## Router Guard Flow

```typescript
router.beforeEach((to) => {
  const resolved = resolveExperience(to)

  if (requiresAuth && !authenticated) {
    // Redirect to owning experience's login
    if (resolved) → resolved.experience.auth.loginRoute
    else → platform default /login
  }

  if (guestOnly && authenticated) {
    // Redirect to owning experience's dashboard
    if (resolved) → resolved.experience.auth.authenticatedRedirect
    else → platform default /home
  }
})
```

## Adding Auth to a New Vertical

1. Add `auth` field to the experience definition in `registry.ts`:
```typescript
export const hisExperience: ExperienceDefinition = {
  key: 'his',
  // ...routes, branding...
  auth: {
    loginRoute: '/his/login',
    authenticatedRedirect: '/t/:tenantSlug/his',
    logoutRedirect: '/his/login',
  },
}
```

2. Create a login page at `experiences/his/auth/HisLoginPage.vue` (or `modules/his/pages/`)
3. Use `useExperienceAuth()` — it auto-resolves the experience from route context
4. Done — no changes to router core, auth store, or other experiences

## Forbidden Patterns

```typescript
// ❌ FORBIDDEN — hardcoded redirects in login pages
await router.push({ name: 'condoflow.dashboard' })
await router.push({ name: 'home' })

// ❌ FORBIDDEN — auth store knows vertical names
if (user.experience === 'condoflow') { ... }

// ❌ FORBIDDEN — router decides role-based redirects
if (to.meta.requiresAdmin) { ... }

// ❌ FORBIDDEN — platform branding in vertical logins
<div>Core Platform</div>  // inside CondoFlowLoginPage

// ❌ FORBIDDEN — duplicated Sanctum logic per experience
await axios.get('/sanctum/csrf-cookie')  // inside login page
```

```typescript
// ✅ CORRECT — experience-driven redirect
const redirectPath = await login(email, password)
await router.push(redirectPath)

// ✅ CORRECT — branding from experience config
<div>{{ branding?.label }}</div>

// ✅ CORRECT — shared auth runtime
const { login, isLoading } = useExperienceAuth()
```

## Future SSO Readiness

This architecture supports future SSO by:
- Auth runtime is already centralized
- Experience auth flows only care about redirects, not how auth happens
- Adding an SSO provider = replacing `authStore.login()` implementation
- Experience definitions remain unchanged

## Related

- [Experience Routing](./experience-routing.md) — route ownership
- [Experience Isolation](./experience-isolation.md) — navigation, branding, shells
