<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Core\IdentityAuth\Events\SanctumTokenRevoked;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeCurrentTokenAction
{
    /**
     * Revoke/delete the current Bearer token.
     *
     * Uses the authenticated user's current access token if available,
     * otherwise falls back to resolving the plain Bearer token.
     * Returns safely if no token is found.
     * Does not revoke all tokens.
     */
    public function execute(Request $request): void
    {
        $user = $request->user();

        if ($user !== null) {
            $currentToken = $user->currentAccessToken();

            if ($currentToken !== null) {
                $tokenName = $currentToken->name;
                $currentToken->delete();

                event(new SanctumTokenRevoked($user, $tokenName, $request->ip(), $request->userAgent()));

                return;
            }
        }

        $plainToken = $request->bearerToken();

        if ($plainToken === null) {
            return;
        }

        // The bearer token may be in the format "id|plainTextToken"
        $parts = explode('|', $plainToken, 2);
        $plainTextToken = $parts[1] ?? $parts[0];

        $accessToken = PersonalAccessToken::findToken($plainTextToken);

        if ($accessToken !== null) {
            /** @var User|null $tokenUser */
            $tokenUser = $accessToken->tokenable;
            $tokenName = $accessToken->name;
            $accessToken->delete();

            event(new SanctumTokenRevoked($tokenUser, $tokenName, $request->ip(), $request->userAgent()));
        }
    }
}
