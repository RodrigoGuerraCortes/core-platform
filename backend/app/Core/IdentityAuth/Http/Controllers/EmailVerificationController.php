<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Controllers;

use App\Core\IdentityAuth\Actions\ResendEmailVerificationAction;
use App\Core\IdentityAuth\Actions\VerifyEmailAction;
use App\Core\IdentityAuth\Http\Requests\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController
{
    public function verifyEmail(
        EmailVerificationRequest $request,
        VerifyEmailAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $wasVerified = $action->execute($user);

        if (! $wasVerified) {
            return response()->json(['message' => 'Email already verified.']);
        }

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resendVerification(
        Request $request,
        ResendEmailVerificationAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $action->execute($user);

        return response()->json([
            'message' => 'If verification is required, a verification email has been sent.',
        ]);
    }
}
