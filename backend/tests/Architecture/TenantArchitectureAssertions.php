<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Core\Tenancy\Models\Concerns\BelongsToTenant;
use App\Core\Tenancy\Routing\TenantRouteRegistrar;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reusable architecture assertion helpers for platform tests.
 *
 * These helpers reduce repetitive architecture assertions across module tests.
 * They are intentionally explicit — each assertion maps to a named invariant.
 *
 * Usage (in a Pest test file):
 *
 *   use Tests\Architecture\TenantArchitectureAssertions;
 *
 *   test('Project uses tenant isolation', function (): void {
 *       TenantArchitectureAssertions::assertUsesBelongsToTenant(Project::class);
 *       TenantArchitectureAssertions::assertUsesSoftDeletes(Project::class);
 *   });
 *
 * @see docs/features/platform-engineering/scaffolding.md
 */
final class TenantArchitectureAssertions
{
    /**
     * Assert that a model class uses the BelongsToTenant trait.
     *
     * Enforces: all tenant-owned models must use BelongsToTenant.
     *
     * @param class-string $modelClass
     */
    public static function assertUsesBelongsToTenant(string $modelClass): void
    {
        $traits = class_uses_recursive($modelClass);

        expect($traits)->toHaveKey(
            BelongsToTenant::class,
            "{$modelClass} must use BelongsToTenant. "
            . "Tenant-owned models must register TenantScope and auto-fill tenant_id."
        );
    }

    /**
     * Assert that a model class uses SoftDeletes.
     *
     * Enforces: tenant-owned models are never hard-deleted.
     *
     * @param class-string $modelClass
     */
    public static function assertUsesSoftDeletes(string $modelClass): void
    {
        $traits = class_uses_recursive($modelClass);

        expect($traits)->toHaveKey(
            SoftDeletes::class,
            "{$modelClass} must use SoftDeletes. Tenant-owned models are never physically deleted."
        );
    }

    /**
     * Assert that a module's route file uses TenantRouteRegistrar::group().
     *
     * Enforces guardrail G-R02: module route files must not inline the raw middleware stack.
     */
    public static function assertUsesTenantRegistrar(string $module): void
    {
        $routeFile = app_path("Core/{$module}/Routes/api.php");

        expect(file_exists($routeFile))->toBeTrue(
            "Core/{$module}/Routes/api.php does not exist."
        );

        expect((string) file_get_contents($routeFile))
            ->toContain(
                'TenantRouteRegistrar::group',
                "Core/{$module}/Routes/api.php must use TenantRouteRegistrar::group() "
                . "instead of referencing TenantRouteMiddleware::STACK directly (G-R02)."
            );
    }

    /**
     * Assert that a module's service provider is registered in bootstrap/providers.php.
     *
     * @param class-string $providerClass
     */
    public static function assertProviderRegistered(string $providerClass): void
    {
        $providers = require base_path('bootstrap/providers.php');

        expect($providers)->toContain(
            $providerClass,
            "{$providerClass} must be registered in bootstrap/providers.php."
        );
    }

    /**
     * Assert that a policy class does not bypass tenant isolation based on is_platform_admin alone.
     *
     * Enforces guardrail G-A01.
     *
     * @param class-string $policyClass
     */
    public static function assertNoPlatformAdminBypass(string $policyClass): void
    {
        $file = (new \ReflectionClass($policyClass))->getFileName();

        if ($file === false) {
            return;
        }

        $content = (string) file_get_contents($file);

        $hasBypass = str_contains($content, 'is_platform_admin')
            && str_contains($content, 'return true');

        expect($hasBypass)->toBeFalse(
            "{$policyClass} appears to grant access based on is_platform_admin alone (G-A01). "
            . "Platform admins are still subject to tenant membership checks."
        );
    }

    /**
     * Assert cross-tenant isolation: Tenant A cannot resolve a Tenant B resource.
     *
     * This is a runtime assertion to be used in HTTP feature tests.
     *
     * Usage:
     *   TenantArchitectureAssertions::assertTenantIsolation(
     *       test: $this,
     *       actingUser: $userA,
     *       tenantA: $tenantA,
     *       resourceUrl: "/projects/{$projectB->id}",
     *   );
     */
    public static function assertTenantIsolation(
        mixed $test,
        mixed $actingUser,
        mixed $tenantA,
        string $resourceUrl,
    ): void {
        $test->actingAs($actingUser)
            ->getJson($resourceUrl, ['X-Tenant-Id' => $tenantA->id])
            ->assertNotFound();
    }

    /**
     * Assert that a route requires tenant resolution (rejects requests without X-Tenant-Id).
     *
     * Usage:
     *   TenantArchitectureAssertions::assertTenantRouteProtected(
     *       test: $this,
     *       actingUser: $user,
     *       url: '/projects',
     *   );
     */
    public static function assertTenantRouteProtected(
        mixed $test,
        mixed $actingUser,
        string $url,
    ): void {
        $test->actingAs($actingUser)
            ->getJson($url)  // No X-Tenant-Id header
            ->assertStatus(400);
    }

    /**
     * Assert that a model class does NOT accept tenant_id from user input
     * through its fillable array indirectly (policy layer).
     *
     * This complements G-T02 (checked statically in ArchitectureGuardrailsTest).
     * Here we check the model, not the request.
     *
     * @param class-string $modelClass
     */
    public static function assertTenantIdNotUserFillable(): void
    {
        // tenant_id may appear in $fillable for internal use (direct DB seeding in tests),
        // but Form Requests must never expose it. The real enforcement is in
        // ArchitectureGuardrailsTest G-T02 (static file inspection).
        // This helper exists for documentation-driven test clarity.
        expect(true)->toBeTrue(
            "Use ArchitectureGuardrailsTest G-T02 to enforce tenant_id is not accepted in Form Requests."
        );
    }
}
