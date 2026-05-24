<?php

declare(strict_types=1);

namespace App\Core\IdentityAuth;

use App\Core\IdentityAuth\Audit\AuthAuditSink;
use App\Core\IdentityAuth\Audit\NullAuthAuditSink;
use App\Core\IdentityAuth\Events\EmailVerified;
use App\Core\IdentityAuth\Events\LoginFailed;
use App\Core\IdentityAuth\Events\PasswordChanged;
use App\Core\IdentityAuth\Events\PasswordResetRequested;
use App\Core\IdentityAuth\Events\SanctumTokenIssued;
use App\Core\IdentityAuth\Events\SanctumTokenRevoked;
use App\Core\IdentityAuth\Events\UserLoggedIn;
use App\Core\IdentityAuth\Events\UserLoggedOut;
use App\Core\IdentityAuth\Events\VerificationEmailResent;
use App\Core\IdentityAuth\Listeners\RecordAuthAuditEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class IdentityAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthAuditSink::class, NullAuthAuditSink::class);
    }

    public function boot(): void
    {
        // IdentityAuth API routes live under /api.
        // EnsureFrontendRequestsAreStateful starts the session for stateful
        // Sanctum SPA requests so login/logout can call $request->session().
        // We avoid the full 'api' group to prevent its SubstituteBindings
        // from conflicting with the tenant middleware ordering.
        Route::middleware([
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ])->prefix('api')->group(__DIR__.'/Routes/api.php');
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

        // Register audit listeners for all Identity/Auth events.
        // RecordAuthAuditEvent converts each event into an AuthAuditEvent and
        // passes it to the AuthAuditSink. The default sink is a no-op.
        $events = $this->app['events'];
        foreach ([
            UserLoggedIn::class,
            UserLoggedOut::class,
            LoginFailed::class,
            SanctumTokenIssued::class,
            SanctumTokenRevoked::class,
            PasswordResetRequested::class,
            PasswordChanged::class,
            EmailVerified::class,
            VerificationEmailResent::class,
        ] as $eventClass) {
            $events->listen($eventClass, RecordAuthAuditEvent::class);
        }
    }
}
