<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

test('valid credentials issue token', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'token_name' => 'test-token',
    ]);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'token',
            'token_type',
            'token_name',
            'created_at',
        ],
    ]);

    $response->assertJson([
        'data' => [
            'token_type' => 'Bearer',
            'token_name' => 'test-token',
        ],
    ]);
});

test('invalid credentials return 401 with generic message', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);

    $response->assertJson([
        'message' => 'Invalid credentials',
        'errors' => [],
    ]);
});

test('issued token can access GET /auth/me', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $tokenResponse = $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $tokenResponse->json('data.token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/auth/me');

    $response->assertStatus(200);

    $response->assertJson([
        'data' => [
            'email' => 'test@example.com',
        ],
    ]);
});

test('current token can be revoked', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $tokenResponse = $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $tokenResponse->json('data.token');

    $revokeResponse = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->deleteJson('/api/auth/token/current');

    $revokeResponse->assertStatus(200);

    $revokeResponse->assertJson([
        'message' => 'Token revoked successfully.',
    ]);
});

test('revoked token can no longer access GET /auth/me', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $tokenResponse = $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $tokenResponse->json('data.token');

    // Revoke the token
    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->deleteJson('/api/auth/token/current');

    // Assert the token was actually deleted from the database
    $this->assertDatabaseCount('personal_access_tokens', 0);

    // Reset auth guards to clear cached auth state
    app('auth')->forgetGuards();

    // Try to access /auth/me with the revoked token
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/auth/me');

    $response->assertStatus(401);
});

test('token response does not include roles, permissions, or tenant context', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);

    $response->assertJsonMissingPath('data.roles');
    $response->assertJsonMissingPath('data.permissions');
    $response->assertJsonMissingPath('data.tenant_id');
    $response->assertJsonMissingPath('data.tenant');
});
