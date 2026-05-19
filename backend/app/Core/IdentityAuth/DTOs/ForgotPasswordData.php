<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\DTOs;

readonly class ForgotPasswordData
{
    public function __construct(
        public string $email,
    ) {}
}
