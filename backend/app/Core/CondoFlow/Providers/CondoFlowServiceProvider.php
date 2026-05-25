<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Providers;

use App\Core\CondoFlow\Models\Building;
use App\Core\CondoFlow\Models\MaintenanceTicket;
use App\Core\CondoFlow\Models\Resident;
use App\Core\CondoFlow\Models\Unit;
use App\Core\CondoFlow\Policies\BuildingPolicy;
use App\Core\CondoFlow\Policies\MaintenanceTicketPolicy;
use App\Core\CondoFlow\Policies\ResidentPolicy;
use App\Core\CondoFlow\Policies\UnitPolicy;
use App\Core\Shared\Providers\CoreModuleServiceProvider;

final class CondoFlowServiceProvider extends CoreModuleServiceProvider
{
    protected array $policies = [
        Building::class => BuildingPolicy::class,
        Unit::class => UnitPolicy::class,
        Resident::class => ResidentPolicy::class,
        MaintenanceTicket::class => MaintenanceTicketPolicy::class,
    ];

    protected function routesPath(): ?string
    {
        return __DIR__ . '/../Routes/api.php';
    }
}
