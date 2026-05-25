<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Enums;

enum UnitStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
}
