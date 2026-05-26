# Experience Isolation — Governance

## Architecture Overview

The platform supports multiple **experiences** — self-contained vertical products that share infrastructure but own their navigation, branding, and route space.

```
src/
├── app/experiences/          # Runtime resolution (types, registry, resolver)
├── experiences/              # Experience-owned assets
│   ├── shared/               # Shared navigation types & composable
│   ├── platform/             # Platform experience (Dashboard, Forms, Reference)
│   │   ├── navigation.ts
│   │   └── branding.ts
│   ├── condoflow/            # CondoFlow experience
│   │   ├── navigation.ts
│   │   └── branding.ts
│   └── his/                  # Future: MiniHIS experience
├── modules/                  # Business logic modules (API, composables, pages)
│   ├── condoflow/
│   ├── dynamic-forms/
│   └── reference/
├── shared/                   # UI primitives, table, feedback (experience-agnostic)
└── layouts/                  # Shell components (experience-aware via composable)
```

## Key Principles

### Runtime vs Experience vs Module

| Layer | Owns | Example |
|-------|------|---------|
| **Runtime** (`app/experiences/`) | Route resolution, guard logic | `resolveExperience()` |
| **Experience** (`experiences/`) | Navigation, branding, shell config | `getCondoflowNavigation()` |
| **Module** (`modules/`) | Business logic, pages, API, tests | `useCondoflow()` |
| **Shared** (`shared/`) | UI primitives, composables | `AppButton`, `AppDataTable` |

### Shared vs Owned

| Shared (experience-agnostic) | Owned (experience-specific) |
|------------------------------|----------------------------|
| `AppButton`, `AppCard` | Navigation items |
| `AppDataTable`, `AppShell` | Branding (label, icon, color) |
| `useAuthStore`, `useTenantStore` | Route definitions |
| Axios client, TanStack Query | Dashboard content |
| Error/loading states | Sidebar content |

## Navigation Ownership

Each experience declares its own navigation factory:

```typescript
// experiences/condoflow/navigation.ts
export function getCondoflowNavigation(tenantSlug: string): NavItem[] {
  return [
    { label: 'Dashboard', name: 'condoflow.dashboard', ... },
    { label: 'Buildings', name: 'condoflow.buildings', ... },
    // NO platform items here
  ]
}
```

The `AppSidebar` uses `useExperienceNavigation()` which auto-resolves the current experience from the route and returns the correct items.

## Shell Ownership

The shell (`AppShell.vue` + `AppSidebar.vue`) is **shared infrastructure** but renders **experience-owned content**:

- Sidebar title/icon → from `ExperienceBranding`
- Navigation items → from experience navigation factory
- Active state detection → from route name prefix matching

No experience-specific `if` blocks inside the shell.

## Adding a New Vertical

1. **Create navigation**: `src/experiences/newvertical/navigation.ts`
2. **Create branding**: `src/experiences/newvertical/branding.ts`
3. **Register in composable**: Add to `navigationProviders` and `brandingProviders` in `useExperienceNavigation.ts`
4. **Register experience**: Add `ExperienceDefinition` in `app/experiences/registry.ts`
5. **Create module**: `src/modules/newvertical/` with pages, API, routes
6. **Done** — shell, guards, and redirects work automatically

## Forbidden Patterns

```typescript
// ❌ Shared global sidebar arrays with all modules
const items = [dashboard, forms, reference, condoflow, his]

// ❌ Platform modules visible inside verticals
// (CondoFlow sidebar showing "Forms" or "Reference")

// ❌ Direct imports between experiences
import { something } from '@/experiences/condoflow/...' // inside platform code

// ❌ Experience-specific logic inside shared shell
if (experience === 'condoflow') { showSpecialHeader() }

// ❌ Shared "home" or "dashboard" assumptions
// Each experience has its own authenticated entry route

// ❌ Hardcoded branding in shell
<v-list-item title="Core Platform" prepend-icon="mdi-domain" />
```

## Correct Patterns

```typescript
// ✅ Experience-aware navigation (auto-resolves from route)
const { items, branding } = useExperienceNavigation()

// ✅ Experience owns its navigation
export function getCondoflowNavigation(slug: string): NavItem[] { ... }

// ✅ Shell renders whatever the experience provides
<v-list-item :title="branding.label" :prepend-icon="branding.icon" />

// ✅ Router guard uses experience resolver (no vertical names)
const resolved = resolveExperience(to)
```

## Branding Isolation

Each experience declares branding metadata:

```typescript
interface ExperienceBranding {
  label: string      // "CondoFlow", "MiniHIS"
  icon?: string      // "mdi-office-building"
  color?: string     // "#1565c0"
}
```

The sidebar title, icon, and (future) theme color are driven by the active experience branding — not hardcoded.

## Testing

```bash
# Experience resolver tests
npx vitest run src/app/experiences/

# Navigation isolation tests
npx vitest run src/experiences/

# Full suite
npx vitest run
```

## Validation Checklist

- [ ] CondoFlow sidebar shows only CondoFlow items
- [ ] Platform sidebar shows only Platform items
- [ ] No cross-experience imports
- [ ] Shell renders experience-owned branding
- [ ] Router guard uses resolver (no `startsWith`)
- [ ] New vertical can be added without touching router or shell
- [ ] All tests green
