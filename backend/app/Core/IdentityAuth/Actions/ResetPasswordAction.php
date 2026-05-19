<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Core\IdentityAuth\DTOs\ResetPasswordData;
use App\Core\IdentityAuth\Events\PasswordChanged;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordAction
{
    /**
     * Reset the user's password using the provided token.
     *
     * Returns true on success, false on invalid or expired token.
     * Does not auto-login the user after reset.
     * Does not revoke existing sessions or tokens.
     * Does not resolve tenant context or roles/permissions.
     */
    public function execute(ResetPasswordData $data): bool
    {
        $status = Password::reset(
            [
                'email' => $data->email,
                'token' => $data->token,
                'password' => $data->password,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordChanged($user, request()->ip(), request()->userAgent()));
            },
        );

        return $status === Password::PASSWORD_RESET;
    }
}
