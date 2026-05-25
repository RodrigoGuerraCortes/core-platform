<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Enums;

enum UnitType: string
{
    case Apartment = 'apartment';
    case Office = 'office';
    case Commercial = 'commercial';
    case Parking = 'parking';
    case Storage = 'storage';
}
