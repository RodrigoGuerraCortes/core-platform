<?php

declare(strict_types=1);

use App\Models\User;

test('unauthenticated request to GET /auth/me returns 401', function (): void {
    $response = $this->getJson('/api/auth/me');

    $response->assertStatus(401);
});

test('authenticated request to GET /auth/me returns user identity', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_platform_admin' => false,
    ]);

    $response = $this->actingAs($user)->getJson('/api/auth/me');

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

    $response->assertJson([
        'data' => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_platform_admin' => false,
        ],
    ]);
});

test('response does not include roles, permissions, or tenant context', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/auth/me');

    $response->assertStatus(200);

    $response->assertJsonMissingPath('data.roles');
    $response->assertJsonMissingPath('data.permissions');
    $response->assertJsonMissingPath('data.tenant_id');
    $response->assertJsonMissingPath('data.tenant');
});
