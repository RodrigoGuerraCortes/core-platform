<?php

declare(strict_types=1);

namespace App\Core\DynamicForms\Enums;

enum FormStatus: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Archived = 'archived';

    public function canAcceptSubmissions(): bool
    {
        return $this === self::Active;
    }

    public function canPublish(): bool
    {
        return $this !== self::Archived;
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }
}
