<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers domain-organized migration paths.
 *
 * Migrations are split into domain folders for ownership visibility:
 *   database/migrations/core/          — users, tenants, cache, jobs, tokens
 *   database/migrations/platform/      — platform-specific (projects, etc.)
 *   database/migrations/dynamic_forms/ — DynamicForms module
 *   database/migrations/condoflow/     — CondoFlow vertical
 *   database/migrations/observability/ — Telescope, logging
 *
 * To add a new domain: add its path to $domainPaths below.
 * Laravel will load migrations from ALL registered paths during migrate/test.
 */
class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Domain migration paths (relative to database/migrations/).
     * Order matters — core must come first for FK dependencies.
     */
    private array $domainPaths = [
        'core',
        'platform',
        'dynamic_forms',
        'condoflow',
        'observability',
    ];

    public function boot(): void
    {
        $basePath = database_path('migrations');

        foreach ($this->domainPaths as $domain) {
            $this->loadMigrationsFrom("{$basePath}/{$domain}");
        }
    }
}

