<?php

declare(strict_types=1);

namespace App\Core\Projects\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
