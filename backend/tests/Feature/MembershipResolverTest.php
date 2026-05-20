<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Support\MembershipResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('roleFor returns the correct membership role', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'admin']);

    $resolver = new MembershipResolver();

    expect($resolver->roleFor($user, $tenant->id))->toBe('admin');
});

test('roleFor returns null when user is not a member', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $resolver = new MembershipResolver();

    expect($resolver->roleFor($user, $tenant->id))->toBeNull();
});

test('roleFor caches the result — second call does not re-query', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'member']);

    $resolver = new MembershipResolver();

    // Prime the cache
    $first = $resolver->roleFor($user, $tenant->id);

    // Detach the user from the tenant so the DB no longer has the row.
    // If roleFor re-queries, it would return null. The cache should return 'member'.
    $tenant->users()->detach($user->id);

    $second = $resolver->roleFor($user, $tenant->id);

    expect($first)->toBe('member')
        ->and($second)->toBe('member'); // served from cache, not from DB
});

test('flush clears the in-memory cache', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $tenant->users()->attach($user, ['membership_role' => 'owner']);

    $resolver = new MembershipResolver();

    // Prime the cache
    $resolver->roleFor($user, $tenant->id);

    // Update the DB record
    $tenant->users()->updateExistingPivot($user->id, ['membership_role' => 'member']);

    // Still cached — returns old value
    expect($resolver->roleFor($user, $tenant->id))->toBe('owner');

    // After flush — re-queries DB
    $resolver->flush();

    expect($resolver->roleFor($user, $tenant->id))->toBe('member');
});

test('cache is keyed per user+tenant combination', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    $tenantA->users()->attach($user, ['membership_role' => 'owner']);
    $tenantB->users()->attach($user, ['membership_role' => 'member']);

    $resolver = new MembershipResolver();

    expect($resolver->roleFor($user, $tenantA->id))->toBe('owner')
        ->and($resolver->roleFor($user, $tenantB->id))->toBe('member');
});

test('MembershipResolver is bound as scoped in the container', function (): void {
    $instanceA = app(MembershipResolver::class);
    $instanceB = app(MembershipResolver::class);

    // Scoped binding — same instance within the same request lifecycle
    expect($instanceA)->toBe($instanceB);
});
