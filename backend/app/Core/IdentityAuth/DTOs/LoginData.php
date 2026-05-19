<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\DTOs;

readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
