<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth\Http\Requests;

use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    // Inherits id/hash parameter validation and signed URL awareness
    // from Laravel's base EmailVerificationRequest.
    // The 'signed' middleware on the route handles URL signature validation.
}
