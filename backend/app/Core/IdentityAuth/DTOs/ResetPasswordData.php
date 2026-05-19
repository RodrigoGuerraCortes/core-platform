<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\DTOs;

readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}
}
