<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Core\IdentityAuth\DTOs\ForgotPasswordData;
use Illuminate\Support\Facades\Password;

class RequestPasswordResetAction
{
    /**
     * Send a password reset link if the email exists.
     *
     * The return value is intentionally discarded by the controller.
     * The caller must always return a generic response to avoid
     * revealing whether the email exists in the system.
     */
    public function execute(ForgotPasswordData $data): void
    {
        Password::sendResetLink(['email' => $data->email]);
    }
}
