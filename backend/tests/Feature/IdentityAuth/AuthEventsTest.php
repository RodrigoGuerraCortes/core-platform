<?php

declare(strict_types=1);

use App\Core\IdentityAuth\Events\EmailVerified;
use App\Core\IdentityAuth\Events\LoginFailed;
use App\Core\IdentityAuth\Events\PasswordChanged;
use App\Core\IdentityAuth\Events\PasswordResetRequested;
use App\Core\IdentityAuth\Events\SanctumTokenIssued;
use App\Core\IdentityAuth\Events\SanctumTokenRevoked;
use App\Core\IdentityAuth\Events\UserLoggedIn;
use App\Core\IdentityAuth\Events\UserLoggedOut;
use App\Core\IdentityAuth\Events\VerificationEmailResent;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->withoutMiddleware(VerifyCsrfToken::class);
});

test('successful session login dispatches UserLoggedIn', function (): void {
    Event::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertStatus(200);

    Event::assertDispatched(UserLoggedIn::class, function (UserLoggedIn $event) use ($user): bool {
        return $event->user->is($user);
    });
});

test('failed session login dispatches LoginFailed', function (): void {
    Event::fake();

    User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(401);

    Event::assertDispatched(LoginFailed::class, function (LoginFailed $event): bool {
        return $event->email === 'test@example.com';
    });
});

test('session logout dispatches UserLoggedOut', function (): void {
    Event::fake();

    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->postJson('/auth/logout')
        ->assertStatus(200);

    Event::assertDispatched(UserLoggedOut::class, function (UserLoggedOut $event) use ($user): bool {
        return $event->user->is($user);
    });
});

test('token issuance dispatches SanctumTokenIssued', function (): void {
    Event::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'token_name' => 'my-token',
    ])->assertStatus(200);

    Event::assertDispatched(SanctumTokenIssued::class, function (SanctumTokenIssued $event) use ($user): bool {
        return $event->user->is($user) && $event->tokenName === 'my-token';
    });
});

test('failed token issuance dispatches LoginFailed', function (): void {
    Event::fake();

    User::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(401);

    Event::assertDispatched(LoginFailed::class, function (LoginFailed $event): bool {
        return $event->email === 'test@example.com';
    });
});

test('token revocation dispatches SanctumTokenRevoked', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->deleteJson('/api/auth/token/current')
        ->assertStatus(200);

    Event::assertDispatched(SanctumTokenRevoked::class, function (SanctumTokenRevoked $event) use ($user): bool {
        return $event->user->is($user);
    });
});

test('forgot password dispatches PasswordResetRequested', function (): void {
    Event::fake();

    $this->postJson('/api/auth/forgot-password', [
        'email' => 'anyone@example.com',
    ])->assertStatus(200);

    Event::assertDispatched(PasswordResetRequested::class, function (PasswordResetRequested $event): bool {
        return $event->email === 'anyone@example.com';
    });
});

test('successful password reset dispatches PasswordChanged', function (): void {
    Event::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertStatus(200);

    Event::assertDispatched(PasswordChanged::class, function (PasswordChanged $event) use ($user): bool {
        return $event->user->is($user);
    });
});

test('successful email verification dispatches EmailVerified', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'auth.email.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->getJson($url)->assertStatus(200);

    Event::assertDispatched(EmailVerified::class, function (EmailVerified $event) use ($user): bool {
        return $event->user->is($user);
    });
});

test('resend verification dispatches VerificationEmailResent for unverified user', function (): void {
    Event::fake();
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->postJson('/api/auth/resend-verification')
        ->assertStatus(200);

    Event::assertDispatched(VerificationEmailResent::class, function (VerificationEmailResent $event) use ($user): bool {
        return $event->user->is($user);
    });
});
