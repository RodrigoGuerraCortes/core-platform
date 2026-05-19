<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Events;

use App\Models\User;

class VerificationEmailResent
{
    public function __construct(
        public readonly User $user,
    ) {}
}
