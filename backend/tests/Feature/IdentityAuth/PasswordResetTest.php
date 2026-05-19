<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('forgot password returns generic success for existing email', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/auth/forgot-password', [
        'email' => 'existing@example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'If that email exists, a password reset link has been sent.',
    ]);
});

test('forgot password returns generic success for unknown email', function (): void {
    $response = $this->postJson('/auth/forgot-password', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'If that email exists, a password reset link has been sent.',
    ]);
});

test('forgot password sends reset notification for existing user', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson('/auth/forgot-password', [
        'email' => 'existing@example.com',
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password succeeds with valid token', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/auth/reset-password', [
        'email' => 'user@example.com',
        'token' => $token,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Password reset successfully.',
    ]);
});

test('reset password fails with invalid token', function (): void {
    User::factory()->create(['email' => 'user@example.com']);

    $response = $this->postJson('/auth/reset-password', [
        'email' => 'user@example.com',
        'token' => 'invalid-token',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Invalid or expired password reset token.',
    ]);
});

test('reset password does not auto-login user', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $token = Password::broker()->createToken($user);

    $this->postJson('/auth/reset-password', [
        'email' => 'user@example.com',
        'token' => $token,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertStatus(200);

    $this->assertGuest('web');
});

test('password reset responses do not include roles permissions or tenant context', function (): void {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $token = Password::broker()->createToken($user);

    $resetResponse = $this->postJson('/auth/reset-password', [
        'email' => 'user@example.com',
        'token' => $token,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $resetResponse->assertStatus(200);
    $resetResponse->assertJsonMissingPath('data.roles');
    $resetResponse->assertJsonMissingPath('data.permissions');
    $resetResponse->assertJsonMissingPath('data.tenant_id');
    $resetResponse->assertJsonMissingPath('data.tenant');

    $forgotResponse = $this->postJson('/auth/forgot-password', [
        'email' => 'user@example.com',
    ]);

    $forgotResponse->assertStatus(200);
    $forgotResponse->assertJsonMissingPath('data.roles');
    $forgotResponse->assertJsonMissingPath('data.permissions');
    $forgotResponse->assertJsonMissingPath('data.tenant_id');
    $forgotResponse->assertJsonMissingPath('data.tenant');
});
