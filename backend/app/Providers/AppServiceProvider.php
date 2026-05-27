<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Shared\Console\Commands\CheckBootstrapCommand;
use App\Core\Shared\Console\Commands\MakeCoreModuleCommand;
use App\Core\Shared\Console\Commands\MakeTenantModelCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckBootstrapCommand::class,
                MakeCoreModuleCommand::class,
                MakeTenantModelCommand::class,
            ]);
        }
    }
}

