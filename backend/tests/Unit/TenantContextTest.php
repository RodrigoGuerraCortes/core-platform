<?php

declare(strict_types=1);

use App\Core\Tenancy\Context\TenantContext;
use App\Core\Tenancy\Models\Tenant;

test('TenantContext is empty by default', function (): void {
    $context = new TenantContext();

    expect($context->tenant())->toBeNull()
        ->and($context->tenantId())->toBeNull()
        ->and($context->isResolved())->toBeFalse();
});

test('TenantContext stores tenant after setTenant', function (): void {
    $context = new TenantContext();

    $tenant = new Tenant();
    $tenant->id = 42;

    $context->setTenant($tenant);

    expect($context->tenant())->toBe($tenant)
        ->and($context->tenantId())->toBe(42)
        ->and($context->isResolved())->toBeTrue();
});

test('TenantContext clears tenant after clear', function (): void {
    $context = new TenantContext();

    $tenant = new Tenant();
    $tenant->id = 1;
    $context->setTenant($tenant);

    $context->clear();

    expect($context->tenant())->toBeNull()
        ->and($context->tenantId())->toBeNull()
        ->and($context->isResolved())->toBeFalse();
});
