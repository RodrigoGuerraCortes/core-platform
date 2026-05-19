<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class IdentityAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');

        // Configure the password reset URL for the ResetPassword notification.
        // Laravel defaults to route('password.reset') which is not defined here.
        // This produces a token+email URL suitable for browser-based clients.
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return url('/auth/reset-password')
                .'?token='.$token
                .'&email='.urlencode((string) $notifiable->getEmailForPasswordReset());
        });

        // Configure the email verification URL for the VerifyEmail notification.
        // Laravel defaults to route('verification.verify') which is not defined here.
        // Generates a 60-minute signed URL pointing to our auth.email.verify route.
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            return URL::temporarySignedRoute(
                'auth.email.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });
    }
}
