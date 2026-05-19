<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('verified user receives already verified response', function (): void {
    $user = User::factory()->create(); // email_verified_at set by default

    $url = URL::temporarySignedRoute(
        'auth.email.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->getJson($url);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Email already verified.']);
});

test('unverified user can be verified with valid signed link', function (): void {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'auth.email.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->getJson($url);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Email verified successfully.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('invalid verification link fails safely', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(
        "/auth/verify-email/{$user->id}/invalid-hash"
    );

    $response->assertStatus(403);
});

test('authenticated unverified user can request verification resend', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->postJson('/auth/resend-verification');

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'If verification is required, a verification email has been sent.',
    ]);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('authenticated verified user can request verification resend safely', function (): void {
    Notification::fake();

    $user = User::factory()->create(); // already verified

    $response = $this->actingAs($user)->postJson('/auth/resend-verification');

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'If verification is required, a verification email has been sent.',
    ]);

    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('unauthenticated user cannot request verification resend', function (): void {
    $response = $this->postJson('/auth/resend-verification');

    $response->assertStatus(401);
});

test('email verification responses do not include roles permissions or tenant context', function (): void {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'auth.email.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $verifyResponse = $this->actingAs($user)->getJson($url);

    $verifyResponse->assertStatus(200);
    $verifyResponse->assertJsonMissingPath('data.roles');
    $verifyResponse->assertJsonMissingPath('data.permissions');
    $verifyResponse->assertJsonMissingPath('data.tenant_id');
    $verifyResponse->assertJsonMissingPath('data.tenant');

    $resendResponse = $this->actingAs($user)->postJson('/auth/resend-verification');

    $resendResponse->assertStatus(200);
    $resendResponse->assertJsonMissingPath('data.roles');
    $resendResponse->assertJsonMissingPath('data.permissions');
    $resendResponse->assertJsonMissingPath('data.tenant_id');
    $resendResponse->assertJsonMissingPath('data.tenant');
});
