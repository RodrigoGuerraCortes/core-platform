<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Controllers;

use App\Core\IdentityAuth\Actions\RequestPasswordResetAction;
use App\Core\IdentityAuth\Actions\ResetPasswordAction;
use App\Core\IdentityAuth\DTOs\ForgotPasswordData;
use App\Core\IdentityAuth\DTOs\ResetPasswordData;
use App\Core\IdentityAuth\Http\Requests\ForgotPasswordRequest;
use App\Core\IdentityAuth\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordController
{
    public function forgotPassword(
        ForgotPasswordRequest $request,
        RequestPasswordResetAction $action,
    ): JsonResponse {
        $action->execute(new ForgotPasswordData(
            email: $request->validated('email'),
        ));

        // Always return the same generic message regardless of whether
        // the email exists, to avoid user enumeration.
        return response()->json([
            'message' => 'If that email exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordAction $action,
    ): JsonResponse {
        $data = new ResetPasswordData(
            email: $request->validated('email'),
            token: $request->validated('token'),
            password: $request->validated('password'),
        );

        $success = $action->execute($data);

        if (! $success) {
            return response()->json([
                'message' => 'Invalid or expired password reset token.',
                'errors' => [],
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}
