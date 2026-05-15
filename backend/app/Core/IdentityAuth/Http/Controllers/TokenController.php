<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Controllers;

use App\Core\IdentityAuth\Actions\IssueSanctumTokenAction;
use App\Core\IdentityAuth\Actions\RevokeCurrentTokenAction;
use App\Core\IdentityAuth\DTOs\TokenIssueData;
use App\Core\IdentityAuth\Http\Requests\TokenIssueRequest;
use App\Core\IdentityAuth\Http\Resources\TokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController
{
    public function issue(
        TokenIssueRequest $request,
        IssueSanctumTokenAction $action,
    ): TokenResource|JsonResponse {
        $data = new TokenIssueData(
            email: $request->validated('email'),
            password: $request->validated('password'),
            tokenName: $request->validated('token_name', 'default'),
        );

        $result = $action->execute($data);

        if ($result === null) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [],
            ], 401);
        }

        return new TokenResource($result);
    }

    public function revokeCurrent(
        Request $request,
        RevokeCurrentTokenAction $action,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $action->execute($request);

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }
}
