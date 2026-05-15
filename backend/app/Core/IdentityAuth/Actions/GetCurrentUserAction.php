<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Models\User;

class GetCurrentUserAction
{
    /**
     * Return the authenticated user identity.
     *
     * No tenant logic, no authorization logic, no business context.
     */
    public function execute(User $user): User
    {
        return $user;
    }
}
