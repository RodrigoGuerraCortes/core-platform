<?php

declare(strict_types=1);

namespace App\Core\Shared\Console\Commands;

use App\Core\Shared\Console\Exceptions\StubNotFoundException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates a tenant-owned model with all required platform primitives.
 *
 * Usage:
 *   php artisan make:tenant-model Project --module=Projects
 *
 * Generates:
 *   app/Core/{Module}/Models/{Model}.php      — uses BelongsToTenant + SoftDeletes
 *   app/Core/{Module}/Policies/{Model}Policy.php
 *   database/factories/{Model}Factory.php
 *   database/migrations/{timestamp}_create_{table}_table.php
 *   tests/Feature/{Module}/{Model}ApiTest.php
 *
 * Does NOT generate:
 *   - Repositories
 *   - Services / DTOs
 *   - Controllers
 *   - Actions
 *
 * @see docs/features/platform-engineering/scaffolding.md
 */
final class MakeTenantModelCommand extends Command
{
    protected $signature = 'make:tenant-model
                            {name : Model name in PascalCase (e.g. Project)}
                            {--module= : Target Core module in PascalCase (e.g. Projects)}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a tenant-owned model with migration, policy, factory, and test skeleton';

    public function handle(): int
    {
        $model  = $this->argument('name');
        $module = $this->option('module') ?? $this->askForModule($model);

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $model)) {
            $this->error("Model name must be PascalCase (e.g. Project). Got: {$model}");

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $module)) {
            $this->error("Module name must be PascalCase (e.g. Projects). Got: {$module}");

            return self::FAILURE;
        }

        $this->info("Scaffolding tenant model {$model} inside Core/{$module}...");

        $this->createModel($model, $module);
        $this->createPolicy($model, $module);
        $this->createFactory($model, $module);
        $this->createMigration($model, $module);
        $this->createFeatureTest($model, $module);

        $this->newLine();
        $this->info('✓ Tenant model scaffolded.');
        $this->newLine();
        $this->warn("Next steps:");
        $this->line("  1. Register the policy in {$module}ServiceProvider::");
        $this->line("       protected array \$policies = [");
        $this->line("           {$model}::class => {$model}Policy::class,");
        $this->line("       ];");
        $this->line("  2. Review the generated migration and adjust columns");
        $this->line("  3. Run: php artisan migrate");
        $this->line("  4. Add routes to Core/{$module}/Routes/api.php");
        $this->newLine();

        return self::SUCCESS;
    }

    private function askForModule(string $model): string
    {
        // Default: pluralise the model name (Project → Projects)
        $default = Str::plural($model);

        return $this->ask("Target module?", $default) ?? $default;
    }

    private function createModel(string $model, string $module): void
    {
        $target = app_path("Core/{$module}/Models/{$model}.php");

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Model already exists (skip): Core/{$module}/Models/{$model}.php");

            return;
        }

        $stub = $this->loadAndReplace('model/Model.stub', $model, $module);
        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Model: Core/{$module}/Models/{$model}.php");
    }

    private function createPolicy(string $model, string $module): void
    {
        $target = app_path("Core/{$module}/Policies/{$model}Policy.php");

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Policy already exists (skip): Core/{$module}/Policies/{$model}Policy.php");

            return;
        }

        $stub = $this->loadAndReplace('policy/Policy.stub', $model, $module);
        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Policy: Core/{$module}/Policies/{$model}Policy.php");
    }

    private function createFactory(string $model, string $module): void
    {
        $target = base_path("database/factories/{$model}Factory.php");

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Factory already exists (skip): database/factories/{$model}Factory.php");

            return;
        }

        $stub = $this->loadAndReplace('model/Factory.stub', $model, $module);
        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Factory: database/factories/{$model}Factory.php");
    }

    private function createMigration(string $model, string $module): void
    {
        $table     = Str::snake(Str::plural($model));
        $timestamp = now()->format('Y_m_d_His');
        $filename  = "{$timestamp}_create_{$table}_table.php";
        $target    = base_path("database/migrations/{$filename}");

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Migration already exists (skip)");

            return;
        }

        $stub = $this->loadAndReplace('migration/create_table.stub', $model, $module);
        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Migration: database/migrations/{$filename}");
    }

    private function createFeatureTest(string $model, string $module): void
    {
        $testDir = base_path("tests/Feature/{$module}");
        if (! is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $target = "{$testDir}/{$model}ApiTest.php";

        if (file_exists($target) && ! $this->option('force')) {
            $this->line("  <fg=yellow>–</> Feature test already exists (skip): tests/Feature/{$module}/{$model}ApiTest.php");

            return;
        }

        $stub = $this->loadAndReplace('tests/FeatureTest.stub', $model, $module);
        file_put_contents($target, $stub);
        $this->line("  <fg=green>✓</> Feature test: tests/Feature/{$module}/{$model}ApiTest.php");
    }

    private function loadAndReplace(string $stubPath, string $model, string $module): string
    {
        $path = base_path("stubs/core-platform/{$stubPath}");

        if (! file_exists($path)) {
            throw new StubNotFoundException("Stub not found: {$path}");
        }

        $stub     = (string) file_get_contents($path);
        $table    = Str::snake(Str::plural($model));
        $resource = Str::kebab(Str::plural($model));
        $modelVar = Str::camel($model);

        return str_replace(
            ['{{ Module }}', '{{ Model }}', '{{ modelVar }}', '{{ resource }}', '{{ table }}'],
            [$module, $model, $modelVar, $resource, $table],
            $stub
        );
    }
}
