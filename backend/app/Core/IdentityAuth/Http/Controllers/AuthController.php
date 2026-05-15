<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Controllers;

use App\Core\IdentityAuth\Actions\GetCurrentUserAction;
use App\Core\IdentityAuth\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    public function currentUser(Request $request, GetCurrentUserAction $action): AuthenticatedUserResource|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $identity = $action->execute($user);

        return new AuthenticatedUserResource($identity);
    }
}
