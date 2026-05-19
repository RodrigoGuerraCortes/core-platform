<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Models\User;

class ResendEmailVerificationAction
{
    /**
     * Send the email verification notification if the user is not yet verified.
     *
     * Silently skips sending if already verified.
     * The caller must always return a generic response to avoid
     * revealing the user's verification status.
     * Does not resolve tenant context or roles/permissions.
     */
    public function execute(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }
}
