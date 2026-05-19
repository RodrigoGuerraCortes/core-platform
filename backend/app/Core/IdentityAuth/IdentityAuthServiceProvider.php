<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth;

use Illuminate\Auth\Notifications\ResetPassword;
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
    }
}
