<?php

declare(strict_types=1);

namespace App\Core\Shared\Console\Commands;

use App\Core\Shared\Console\Exceptions\StubNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates a minimal module skeleton aligned with platform conventions.
 *
 * Usage:
 *   php artisan make:core-module Projects
 *
 * Generates:
 *   app/Core/{Module}/
 *   ├── Http/            (empty, ready for controllers)
 *   ├── Models/          (empty, ready for models)
 *   ├── Policies/        (empty, ready for policies)
 *   ├── Providers/       {Module}ServiceProvider.php
 *   ├── Routes/          api.php (uses TenantRouteRegistrar)
 *   ├── Tests/           {Module}ArchitectureTest.php
 *   └── README.md
 *
 * Does NOT generate:
 *   - Controllers
 *   - Actions / Services
 *   - Models (use make:tenant-model for that)
 *
 * @see docs/features/platform-engineering/scaffolding.md
 */
final class MakeCoreModuleCommand extends Command
{
    protected $signature = 'make:core-module
                            {name : Module name in PascalCase (e.g. Projects)}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a minimal Core module skeleton (provider, routes, test stub, readme)';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $name)) {
            $this->error("Module name must be PascalCase (e.g. Projects). Got: {$name}");

            return self::FAILURE;
        }

        $this->info("Scaffolding Core/{$name} module...");

        $this->createDirectories($name);
        $this->createProvider($name);
        $this->createRoutes($name);
        $this->createReadme($name);
        $this->createArchitectureTest($name);

        $this->newLine();
        $this->info('✓ Module skeleton created.');
        $this->newLine();
        $this->warn("Next steps:");
        $this->line("  1. Register App\\Core\\{$name}\\Providers\\{$name}ServiceProvider::class");
        $this->line("     in bootstrap/providers.php (after TenancyServiceProvider)");
        $this->line("  2. Run php artisan make:tenant-model <ModelName> --module={$name}");
        $this->line("     to add a tenant-owned model");
        $this->line("  3. Add routes to app/Core/{$name}/Routes/api.php");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Create the standard module directory structure.
     * Empty directories get a .gitkeep so they appear in git.
     */
    private function createDirectories(string $name): void
    {
        $base = app_path("Core/{$name}");

        $dirs = ['Http', 'Models', 'Policies', 'Providers', 'Routes'];

        foreach ($dirs as $dir) {
            $path = "{$base}/{$dir}";
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        // Http and Models are intentionally left empty — gitkeep for tracking.
        foreach (['Http', 'Models', 'Policies'] as $empty) {
            $gitkeep = "{$base}/{$empty}/.gitkeep";
            if (! file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
            }
        }

        // Tests directory (outside app/, inside tests/)
        $testDir = base_path("tests/Feature/{$name}");
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $this->line("  <fg=green>✓</> Directories created");
    }

    private function createProvider(string $name): void
    {
        $target = app_path("Core/{$name}/Providers/{$name}ServiceProvider.php");

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Provider already exists (skip): Core/{$name}/Providers/{$name}ServiceProvider.php");

            return;
        }

        $stub = $this->loadStub('provider/ModuleServiceProvider.stub');
        $stub = $this->replace($stub, $name);

        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Provider: Core/{$name}/Providers/{$name}ServiceProvider.php");
    }

    private function createRoutes(string $name): void
    {
        $target = app_path("Core/{$name}/Routes/api.php");

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Routes already exist (skip): Core/{$name}/Routes/api.php");

            return;
        }

        $stub = $this->loadStub('routes/api.stub');
        $stub = $this->replace($stub, $name);

        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Routes: Core/{$name}/Routes/api.php");
    }

    private function createReadme(string $name): void
    {
        $target = app_path("Core/{$name}/README.md");

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> README already exists (skip): Core/{$name}/README.md");

            return;
        }

        $stub = $this->loadStub('readme/README.stub');
        $stub = $this->replace($stub, $name);

        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> README: Core/{$name}/README.md");
    }

    private function createArchitectureTest(string $name): void
    {
        // Architecture test for the module lives in tests/Feature/{Module}/
        $target = base_path("tests/Feature/{$name}/{$name}ArchitectureTest.php");

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Architecture test already exists (skip)");

            return;
        }

        // Use the module architecture test stub (no model-specific parts)
        $stub = $this->moduleArchitectureTestStub($name);

        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Architecture test: tests/Feature/{$name}/{$name}ArchitectureTest.php");
    }

    /**
     * Generate a minimal module architecture test (no model stubs — models come later).
     */
    private function moduleArchitectureTestStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

// ─── {$name} module architecture ─────────────────────────────────────────────
//
// These tests verify structural invariants for the {$name} module.
// They run against file content, not runtime behaviour.
// Add model/policy checks when you run make:tenant-model.

test('{$name}ServiceProvider is registered in bootstrap/providers', function (): void {
    \$providers = require base_path('bootstrap/providers.php');

    expect(\$providers)->toContain(\\App\\Core\\{$name}\\Providers\\{$name}ServiceProvider::class);
});

test('{$name} route file uses TenantRouteRegistrar', function (): void {
    \$routeFile = app_path('Core/{$name}/Routes/api.php');

    expect(file_exists(\$routeFile))->toBeTrue('{$name} route file does not exist');
    expect(file_get_contents(\$routeFile))->toContain('TenantRouteRegistrar::group');
});
PHP;
    }

    private function loadStub(string $relativePath): string
    {
        $path = base_path("stubs/core-platform/{$relativePath}");

        if (! file_exists($path)) {
            throw new StubNotFoundException("Stub not found: {$path}");
        }

        return (string) file_get_contents($path);
    }

    /**
     * Replace all {{ Placeholder }} tokens in a stub string.
     */
    private function replace(string $stub, string $module, string $model = ''): string
    {
        $resource = Str::kebab(Str::plural($model ?: $module));
        $modelVar = Str::camel($model ?: $module);

        return str_replace(
            ['{{ Module }}', '{{ Model }}', '{{ modelVar }}', '{{ resource }}', '{{ table }}'],
            [$module, $model ?: $module, $modelVar, $resource, Str::snake(Str::plural($model ?: $module))],
            $stub
        );
    }
}
