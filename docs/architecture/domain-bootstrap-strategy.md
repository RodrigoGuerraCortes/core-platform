# Domain Bootstrap Strategy

## Architecture Overview

The platform uses a **domain-organized bootstrap** model:

```
database/
├── migrations/
│   ├── core/           → users, tenants, cache, jobs, tokens
│   ├── platform/       → projects (platform-specific)
│   ├── dynamic_forms/  → forms, versions, submissions
│   ├── condoflow/      → buildings, units, residents, tickets
│   └── observability/  → telescope
│
├── seeders/
│   ├── Core/
│   │   └── CoreSeeder.php           → admin users, Acme tenant
│   ├── CondoFlow/
│   │   └── CondoFlowSeeder.php      → realistic vertical data
│   ├── Development/
│   │   └── DevelopmentBootstrapSeeder.php  → orchestrator
│   └── DatabaseSeeder.php           → entry point
│
└── factories/
    └── CondoFlow/                   → model factories
```

## Bootstrap Flow

```
php artisan db:seed
  → DatabaseSeeder (gates on non-production)
    → DevelopmentBootstrapSeeder (orchestrator)
      → CoreSeeder            (platform admin, Acme tenant, memberships)
      → CondoFlowSeeder       (buildings, units, residents, tickets)
      → (future) MiniHisSeeder
      → (future) ERPSeeder
```

## Domain Ownership

| Domain | Migrations | Seeders | Owner |
|--------|-----------|---------|-------|
| Core | users, tenants, tokens, cache, jobs | CoreSeeder | Platform team |
| Platform | projects | — | Platform team |
| DynamicForms | forms, versions, submissions | — | Forms module |
| CondoFlow | buildings, units, residents, tickets | CondoFlowSeeder | CondoFlow vertical |
| Observability | telescope | — | Platform team |

## Vertical Isolation Strategy

Each vertical:
- Owns its migrations in `database/migrations/<domain>/`
- Owns its seeders in `database/seeders/<Domain>/`
- Sets TenantContext before creating tenant-scoped models
- Creates its own tenant(s) for isolation testing
- Seeds into shared `acme` tenant for cross-experience testing

## Future Extraction Readiness

If a vertical needs to be extracted:
1. Its migrations are already isolated in one folder
2. Its seeders are self-contained
3. Its models/controllers are in `app/Core/<Domain>/`
4. Only shared infrastructure (auth, tenancy) needs resolution

## Forbidden Patterns

- ❌ Vertical business data in CoreSeeder
- ❌ Platform assumptions in vertical seeders
- ❌ Cross-vertical seeder dependencies
- ❌ Migrations in the root `database/migrations/` folder
- ❌ Hardcoded tenant IDs in seeders (use firstOrCreate)

## MiniHIS Onboarding Strategy

To add MiniHIS:
1. Create `database/migrations/his/` with domain tables
2. Create `database/seeders/MiniHis/MiniHisSeeder.php`
3. Add `'his'` to `MigrationServiceProvider::$domainPaths`
4. Add `MiniHisSeeder::class` to `DevelopmentBootstrapSeeder`
5. Register experience in frontend `registry.ts`
6. Done — zero changes to core
