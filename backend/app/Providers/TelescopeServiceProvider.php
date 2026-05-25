<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Tenancy\Contracts\TenantContextContract;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Telescope Service Provider — platform-aware configuration.
 *
 * Responsibilities:
 *   1. Gate access to platform admins only
 *   2. Tag entries with tenant:{slug}, user:{id}, module:{name}
 *   3. Filter/prune entries by environment
 *   4. Disable in production unless TELESCOPE_ENABLED=true
 *
 * Telescope is registered ONLY in local/staging environments (see register()).
 * In production, it is disabled by default via TELESCOPE_ENABLED env var.
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register Telescope services — environment-aware.
     */
    public function register(): void
    {
        // Disable in production by default
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        // Only record entries when Telescope is enabled
        Telescope::filter(function (IncomingEntry $entry): bool {
            if ($this->app->environment('local', 'testing', 'staging')) {
                return true;
            }

            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });

        // Custom tags: tenant, user, module
        Telescope::tag(function (IncomingEntry $entry): array {
            $tags = [];

            // Tenant context
            $tenantContext = app(TenantContextContract::class);
            if ($tenantContext->isResolved()) {
                $tenant = $tenantContext->tenant();
                $tags[] = "tenant:{$tenant->slug}";
            }

            // Authenticated user
            if ($userId = $entry->user?->id ?? auth()->id()) {
                $tags[] = "user:{$userId}";
            }

            // Module detection from URI
            $uri = $entry->content['uri'] ?? '';
            if (preg_match('#^/api/([^/]+)#', $uri, $m)) {
                $tags[] = "module:{$m[1]}";
            }

            return $tags;
        });
    }

    /**
     * Hide sensitive request details from Telescope entries.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Gate Telescope access to platform admins only.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user): bool {
            return (bool) ($user->is_platform_admin ?? false);
        });
    }
}
