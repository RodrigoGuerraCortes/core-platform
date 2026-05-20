<?php

declare(strict_types=1);

namespace App\Core\Projects\Providers;

use App\Core\Projects\Models\Project;
use App\Core\Projects\Policies\ProjectPolicy;
use App\Core\Shared\Providers\CoreModuleServiceProvider;

final class ProjectsServiceProvider extends CoreModuleServiceProvider
{
    protected array $policies = [
        Project::class => ProjectPolicy::class,
    ];

    protected function routesPath(): ?string
    {
        return __DIR__ . '/../Routes/api.php';
    }
}
