<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Core\IdentityAuth\DTOs\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    /**
     * Attempt session-based authentication with the given credentials.
     *
     * Returns the authenticated User on success, null on failure.
     * Does not issue tokens. Does not resolve tenant context or roles.
     */
    public function execute(LoginData $data): ?User
    {
        $credentials = [
            'email' => $data->email,
            'password' => $data->password,
        ];

        if (! Auth::attempt($credentials)) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
