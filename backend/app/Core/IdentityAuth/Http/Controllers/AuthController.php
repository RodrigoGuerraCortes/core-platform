<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Controllers;

use App\Core\IdentityAuth\Actions\GetCurrentUserAction;
use App\Core\IdentityAuth\Actions\LoginUserAction;
use App\Core\IdentityAuth\Actions\LogoutUserAction;
use App\Core\IdentityAuth\DTOs\LoginData;
use App\Core\IdentityAuth\Http\Requests\LoginRequest;
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

    public function login(LoginRequest $request, LoginUserAction $action): AuthenticatedUserResource|JsonResponse
    {
        $loginData = new LoginData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );

        $user = $action->execute($loginData);

        if ($user === null) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [],
            ], 401);
        }

        $request->session()->regenerate();

        return new AuthenticatedUserResource($user);
    }

    public function logout(Request $request, LogoutUserAction $action): JsonResponse
    {
        $action->execute($request);

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
