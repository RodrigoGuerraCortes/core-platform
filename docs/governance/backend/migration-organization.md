# Migration Organization — Governance

## Structure

All migrations are organized by domain under `database/migrations/`:

```
database/migrations/
├── core/            → Framework & identity (users, tenants, cache, jobs, tokens)
├── platform/        → Platform-level features (projects)
├── dynamic_forms/   → DynamicForms module tables
├── condoflow/       → CondoFlow vertical tables
├── observability/   → Telescope, future logging tables
└── his/             → (future) MiniHIS tables
```

## How Laravel Loads Them

`MigrationServiceProvider` (registered in `bootstrap/providers.php`) calls `loadMigrationsFrom()` for each domain path. Order matters — `core` first for FK dependencies.

```php
// app/Providers/MigrationServiceProvider.php
private array $domainPaths = [
    'core',
    'platform',
    'dynamic_forms',
    'condoflow',
    'observability',
];
```

## Adding a New Domain

1. Create folder: `database/migrations/<domain>/`
2. Add to `$domainPaths` in `MigrationServiceProvider`
3. Create migrations with standard naming: `YYYY_MM_DD_HHMMSS_description.php`

## Naming Conventions

- File: `{date}_{description}.php` (standard Laravel)
- Table prefix: domain name for clarity (e.g., `condoflow_` prefix NOT required — table name matches model)
- FK references: use standard `->constrained()` 

## Ownership Boundaries

| If the table belongs to... | Put migration in... |
|---------------------------|---------------------|
| Framework/auth/identity | `core/` |
| Platform admin features | `platform/` |
| A vertical module | `<module_name>/` |
| Monitoring/observability | `observability/` |

## Forbidden

- ❌ Migrations in root `database/migrations/` (must be in a domain folder)
- ❌ Cross-domain FKs without explicit documentation
- ❌ Vertical migrations depending on other vertical tables
- ❌ Removing `MigrationServiceProvider` registration

## Commands

All standard Laravel commands work unchanged:

```bash
php artisan migrate
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:status
php artisan test  # (uses RefreshDatabase trait)
```
