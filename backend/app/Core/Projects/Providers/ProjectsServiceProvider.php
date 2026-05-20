<?php

declare(strict_types=1);

namespace App\Core\Projects\Providers;

use App\Core\Projects\Models\Project;
use App\Core\Projects\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProjectsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
