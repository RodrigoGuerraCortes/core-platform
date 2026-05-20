<?php

declare(strict_types=1);

/**
 * Architecture Guardrail Tests.
 *
 * These tests enforce the platform invariants defined in:
 *   docs/features/platform-engineering/architecture-guardrails.md
 *
 * They inspect file contents rather than runtime behavior.
 * Failures here indicate a guardrail was violated — fix the offending
 * code, not the test.
 *
 * Philosophy:
 *   - Each test maps to a named guardrail (G-T01, G-R02, etc.)
 *   - Tests are intentionally simple: grep-based, no reflection
 *   - False negatives are acceptable; false positives are not
 *   - These are a safety net, not a complete static analysis suite
 */

use App\Core\Tenancy\Routing\TenantRouteRegistrar;

// ---------------------------------------------------------------------------
// Helpers (local to this file)
// ---------------------------------------------------------------------------

/**
 * Recursively find all .php files under a given directory.
 *
 * @return string[]
 */
function phpFilesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * Filter a list of file paths to those ending with a given suffix.
 *
 * @param  string[] $files
 * @return string[]
 */
function filterBySuffix(array $files, string $suffix): array
{
    return array_values(array_filter($files, fn (string $f): bool => str_ends_with($f, $suffix)));
}

/**
 * Return files that contain the given needle string.
 *
 * @param  string[] $files
 * @return string[]
 */
function filesContaining(array $files, string $needle): array
{
    return array_values(
        array_filter($files, fn (string $f): bool => str_contains((string) file_get_contents($f), $needle))
    );
}

/**
 * Strip the app path prefix for readable violation messages.
 *
 * @param  string[] $paths
 * @return string[]
 */
function relativeToApp(array $paths): array
{
    $prefix = app_path() . '/';

    return array_map(fn (string $p): string => str_replace($prefix, '', $p), $paths);
}

// ---------------------------------------------------------------------------
// G-T03 — withoutGlobalScopes() (plural) is FORBIDDEN
// ---------------------------------------------------------------------------

it('G-T03: no app code calls ->withoutGlobalScopes() (plural form)', function (): void {
    $allFiles = phpFilesUnder(app_path());

    // Search for actual method invocations (`->withoutGlobalScopes()`), not
    // comment text that warns against the pattern.
    $violations = array_values(array_filter(
        $allFiles,
        fn (string $f): bool => (bool) preg_match('/->withoutGlobalScopes\(\)/', (string) file_get_contents($f))
    ));

    $violations = relativeToApp($violations);

    expect($violations)->toBeEmpty(
        "G-T03 violation — ->withoutGlobalScopes() is forbidden.\n"
        . "Use ->withoutGlobalScope(TenantScope::class) instead.\n"
        . "Found in:\n" . implode("\n", $violations)
    );
});

// ---------------------------------------------------------------------------
// G-T02 — No form request accepts tenant_id from user input
// ---------------------------------------------------------------------------

it('G-T02: no Form Request declares tenant_id as a validation rule', function (): void {
    $requestFiles = filterBySuffix(phpFilesUnder(app_path()), 'Request.php');

    $violations = array_values(array_filter(
        $requestFiles,
        function (string $file): bool {
            $content = (string) file_get_contents($file);
            // Match 'tenant_id' => or "tenant_id" => as an array key in rules()
            return (bool) preg_match('/[\'"]tenant_id[\'"]\s*=>/', $content);
        }
    ));

    $violations = relativeToApp($violations);

    expect($violations)->toBeEmpty(
        "G-T02 violation — Form requests must not accept tenant_id from user input.\n"
        . "tenant_id is auto-filled by BelongsToTenant from TenantContextContract.\n"
        . "Found in:\n" . implode("\n", $violations)
    );
});

// ---------------------------------------------------------------------------
// G-T05 — Controllers must not read X-Tenant-Id directly
// ---------------------------------------------------------------------------

it('G-T05: no controller reads X-Tenant-Id directly', function (): void {
    $controllerFiles = filterBySuffix(phpFilesUnder(app_path()), 'Controller.php');

    $violations = relativeToApp(filesContaining($controllerFiles, 'X-Tenant-Id'));

    expect($violations)->toBeEmpty(
        "G-T05 violation — Controllers must not read X-Tenant-Id directly.\n"
        . "Inject TenantContextContract to access the resolved tenant.\n"
        . "Found in:\n" . implode("\n", $violations)
    );
});

// ---------------------------------------------------------------------------
// G-R02 — Module route files must use TenantRouteRegistrar, not raw STACK
// ---------------------------------------------------------------------------

it('G-R02: module route files use TenantRouteRegistrar::group() not raw TenantRouteMiddleware::STACK', function (): void {
    // Collect all module route files (Core and Domain)
    $coreRoutes  = glob(app_path('Core/*/Routes/api.php')) ?: [];
    $domainRoutes = glob(app_path('Domain/*/Routes/api.php')) ?: [];
    $routeFiles  = array_merge($coreRoutes, $domainRoutes);

    $violations = relativeToApp(filesContaining($routeFiles, 'TenantRouteMiddleware::STACK'));

    expect($violations)->toBeEmpty(
        "G-R02 violation — Module route files must call TenantRouteRegistrar::group()\n"
        . "instead of Route::middleware(TenantRouteMiddleware::STACK)->group().\n"
        . "Found in:\n" . implode("\n", $violations)
    );
});

// ---------------------------------------------------------------------------
// G-R02 (structural) — TenantRouteRegistrar delegates to TenantRouteMiddleware::STACK
// ---------------------------------------------------------------------------

it('G-R02 structural: TenantRouteRegistrar is a thin wrapper around TenantRouteMiddleware::STACK', function (): void {
    // Confirm the registrar class exists and references STACK so refactoring
    // TenantRouteMiddleware still works end-to-end.
    $registrarFile = app_path('Core/Tenancy/Routing/TenantRouteRegistrar.php');

    expect(file_exists($registrarFile))->toBeTrue('TenantRouteRegistrar.php does not exist');

    $content = (string) file_get_contents($registrarFile);

    expect($content)
        ->toContain(TenantRouteRegistrar::class === TenantRouteRegistrar::class ? 'TenantRouteMiddleware::STACK' : '')
        ->toContain('TenantRouteMiddleware::STACK');
});

// ---------------------------------------------------------------------------
// G-A01 — No policy checks is_platform_admin for bypass
// ---------------------------------------------------------------------------

it('G-A01: no policy class returns true based on is_platform_admin alone', function (): void {
    $policyFiles = filterBySuffix(phpFilesUnder(app_path()), 'Policy.php');

    // Detect the pattern: checking is_platform_admin and returning true (bypass)
    // This is a heuristic — it catches the most obvious form of the violation.
    $violations = array_values(array_filter(
        $policyFiles,
        function (string $file): bool {
            $content = (string) file_get_contents($file);

            return str_contains($content, 'is_platform_admin')
                && str_contains($content, 'return true');
        }
    ));

    $violations = relativeToApp($violations);

    expect($violations)->toBeEmpty(
        "G-A01 violation — Policies must not grant access based on is_platform_admin alone.\n"
        . "Platform admins are subject to tenant membership checks.\n"
        . "Found in:\n" . implode("\n", $violations)
    );
});
