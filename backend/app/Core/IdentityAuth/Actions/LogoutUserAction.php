<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutUserAction
{
    /**
     * Logout the current session user.
     *
     * Invalidates the session and regenerates the CSRF token.
     * Does not revoke Sanctum tokens or manage API credentials.
     */
    public function execute(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }
}
