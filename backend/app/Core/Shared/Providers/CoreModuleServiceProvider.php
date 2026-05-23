<?php

declare(strict_types=1);

namespace App\Core\Shared\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for all Core (and Domain) modules.
 *
 * Centralizes the bootstrapping conventions defined in:
 *   docs/features/platform-engineering/service-provider-conventions.md
 *
 * Subclasses declare:
 *   - $policies: model-class → policy-class map, registered automatically
 *   - routesPath(): absolute path to Routes/api.php, or null if no routes
 *
 * Subclasses may override boot() to add listeners or observers,
 * but MUST call parent::boot() to preserve route and policy loading.
 *
 * Example:
 *
 *   final class ProjectsServiceProvider extends CoreModuleServiceProvider
 *   {
 *       protected array $policies = [
 *           Project::class => ProjectPolicy::class,
 *       ];
 *
 *       protected function routesPath(): ?string
 *       {
 *           return __DIR__ . '/../Routes/api.php';
 *       }
 *   }
 */
abstract class CoreModuleServiceProvider extends ServiceProvider
{
    /**
     * Model-to-policy class map.
     * Entries are registered via Gate::policy() during boot().
     *
     * @var array<class-string, class-string>
     */
    protected array $policies = [];

    /**
     * Boot the module: load routes, register policies.
     * Subclasses that override this MUST call parent::boot().
     */
    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootPolicies();
    }

    private function bootRoutes(): void
    {
        $path = $this->routesPath();

        if ($path !== null) {
            // All Core module routes are served under the /api prefix.
            // Modules define clean relative paths (e.g. /forms) — the prefix
            // is applied once here so it is never repeated in route files.
            Route::prefix('api')->group($path);
        }
    }

    private function bootPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Return the absolute path to the module's Routes/api.php.
     * Return null if this module exposes no HTTP routes.
     */
    protected function routesPath(): ?string
    {
        return null;
    }

    /**
     * Load module-owned database migrations from the given directory.
     * Call from a subclass boot() when the module ships its own migrations.
     */
    final protected function loadModuleMigrations(string $path): void
    {
        $this->loadMigrationsFrom($path);
    }

    /**
     * Merge a module-owned config file under the given key.
     * Call from a subclass boot() when the module ships a default config file.
     */
    final protected function loadModuleConfig(string $path, string $key): void
    {
        $this->mergeConfigFrom($path, $key);
    }
}
