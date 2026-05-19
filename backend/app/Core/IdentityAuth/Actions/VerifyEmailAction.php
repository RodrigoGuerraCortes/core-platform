<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Core\IdentityAuth\Events\EmailVerified;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailAction
{
    /**
     * Mark the user's email as verified.
     *
     * Returns true if the email was newly verified.
     * Returns false if the email was already verified.
     * Does not auto-login the user.
     * Does not resolve tenant context or roles/permissions.
     */
    public function execute(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->markEmailAsVerified();

        event(new Verified($user));
        event(new EmailVerified($user));

        return true;
    }
}
