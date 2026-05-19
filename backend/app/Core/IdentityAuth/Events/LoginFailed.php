<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Events;

class LoginFailed
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {}
}
