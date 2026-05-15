<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\DTOs;

readonly class TokenIssueData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $tokenName = 'default',
    ) {}
}
