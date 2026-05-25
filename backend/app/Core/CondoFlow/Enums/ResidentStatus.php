<?php

declare(strict_types=1);

namespace App\Core\CondoFlow\Enums;

enum ResidentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
