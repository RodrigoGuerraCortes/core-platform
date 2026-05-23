<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

beforeEach(function (): void {
    $this->withoutMiddleware(VerifyCsrfToken::class);
});

test('valid credentials create session login', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'email',
            'email_verified_at',
            'is_platform_admin',
        ],
    ]);

    $this->assertAuthenticatedAs($user, 'web');
});

test('invalid credentials return 401 with generic message', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);

    $response->assertJson([
        'message' => 'Invalid credentials',
        'errors' => [],
    ]);
});

test('session-authenticated user can access GET /auth/me', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertStatus(200);

    $response = $this->actingAs($user, 'web')->getJson('/api/auth/me');

    $response->assertStatus(200);

    $response->assertJson([
        'data' => [
            'email' => 'test@example.com',
        ],
    ]);
});

test('logout invalidates session', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertStatus(200);

    $this->actingAs($user, 'web')
        ->postJson('/auth/logout')
        ->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully.']);

    $this->getJson('/api/auth/me')->assertStatus(401);
});

test('logout does not revoke Sanctum tokens', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertStatus(200);

    $this->actingAs($user, 'web')->postJson('/auth/logout');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/auth/me');

    $response->assertStatus(200);
});

test('session login response does not include roles, permissions, or tenant context', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);

    $response->assertJsonMissingPath('data.roles');
    $response->assertJsonMissingPath('data.permissions');
    $response->assertJsonMissingPath('data.tenant_id');
    $response->assertJsonMissingPath('data.tenant');
});
