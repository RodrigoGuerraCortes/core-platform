<?php

declare(strict_types=1);

use App\Models\User;

test('platform admin can access Filament admin panel', function (): void {
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $response = $this->actingAs($admin, 'web')->get('/admin');

    // Platform admin must not be denied (403) or sent to login
    expect($response->getStatusCode())->not->toBe(403);
    expect($response->getStatusCode())->not->toBe(401);

    if ($response->isRedirect()) {
        expect($response->headers->get('Location', ''))->not->toContain('/admin/login');
    }
});

test('non-platform admin cannot access Filament admin panel', function (): void {
    $user = User::factory()->create(['is_platform_admin' => false]);

    $this->actingAs($user, 'web')
        ->get('/admin')
        ->assertForbidden();
});

test('unauthenticated user is redirected to Filament login', function (): void {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

test('API Bearer token does not grant Filament panel access', function (): void {
    $admin = User::factory()->create(['is_platform_admin' => true]);
    $token = $admin->createToken('test-token')->plainTextToken;

    // Filament uses the web/session guard — Bearer token is not recognised
    $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->get('/admin')
        ->assertRedirect('/admin/login');
});
