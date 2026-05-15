<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Actions;

use App\Core\IdentityAuth\DTOs\TokenIssueData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class IssueSanctumTokenAction
{
    /**
     * Issue a Sanctum personal access token.
     *
     * Returns null on invalid credentials (generic error).
     * Does not expose whether the email exists.
     * Does not resolve tenant context or roles/permissions.
     */
    public function execute(TokenIssueData $data): ?NewAccessToken
    {
        $user = User::where('email', $data->email)->first();

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            return null;
        }

        // Optional: check email verification only if easy and clean
        // (not enforced in Phase 1)

        $tokenName = $data->tokenName;

        /** @var NewAccessToken $token */
        $token = $user->createToken($tokenName);

        return $token;
    }
}
