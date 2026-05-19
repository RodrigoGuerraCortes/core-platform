<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Events;

use App\Models\User;

class SanctumTokenRevoked
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $tokenName,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {}
}
