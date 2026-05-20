<?php

declare(strict_types=1);

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Jobs\Concerns\HasTenantContext;
use App\Core\Tenancy\Jobs\Middleware\RestoreTenantContext;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(TenantContextContract::class)->clear();
});

// ─── Capture at dispatch time ─────────────────────────────────────────────────

test('job captures tenant id at dispatch time', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $job = new class {
        use HasTenantContext;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void {}
    };

    expect($job->tenantId)->toBe($tenant->id);
});

test('job captures null tenant id when no context is set', function (): void {
    // No context set — platform-level job
    $job = new class {
        use HasTenantContext;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void {}
    };

    expect($job->tenantId)->toBeNull();
});

// ─── Context restoration ──────────────────────────────────────────────────────

test('RestoreTenantContext restores tenant context before handle()', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $job = new class {
        use HasTenantContext;

        public ?int $seenTenantId = null;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void
        {
            $this->seenTenantId = app(TenantContextContract::class)->tenantId();
        }
    };

    // Simulate worker environment: context cleared between dispatch and execution.
    app(TenantContextContract::class)->clear();
    expect(app(TenantContextContract::class)->isResolved())->toBeFalse();

    (new RestoreTenantContext())->handle($job, fn ($j) => $j->handle());

    expect($job->seenTenantId)->toBe($tenant->id);
});

// ─── Worker cleanup (finally block) ──────────────────────────────────────────

test('TenantContext is cleared after successful job execution', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $job = new class {
        use HasTenantContext;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void {}
    };

    app(TenantContextContract::class)->clear();

    (new RestoreTenantContext())->handle($job, fn ($j) => $j->handle());

    expect(app(TenantContextContract::class)->isResolved())->toBeFalse();
});

test('TenantContext is cleared even when job handle() throws', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $job = new class {
        use HasTenantContext;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void
        {
            throw new \RuntimeException('Intentional job failure');
        }
    };

    app(TenantContextContract::class)->clear();

    try {
        (new RestoreTenantContext())->handle($job, fn ($j) => $j->handle());
    } catch (\RuntimeException) {
        // Expected — job failed intentionally.
    }

    // Context MUST be cleared regardless of the failure.
    expect(app(TenantContextContract::class)->isResolved())->toBeFalse();
});

// ─── Worker leakage prevention ────────────────────────────────────────────────

test('tenant context does not leak between consecutive jobs', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    // Job 1 — has tenant context.
    $job1 = new class {
        use HasTenantContext;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void {}
    };

    // Job 2 — dispatched from platform context (no tenant).
    $job2 = new class {
        use HasTenantContext;

        public ?int $seenTenantId = null;

        public function handle(): void
        {
            $this->seenTenantId = app(TenantContextContract::class)->tenantId();
        }
    };
    // tenantId is null — captureTenantContext() was NOT called.

    app(TenantContextContract::class)->clear();
    $middleware = new RestoreTenantContext();

    // Process job 1 (context is restored then cleared in finally).
    $middleware->handle($job1, fn ($j) => $j->handle());

    // Process job 2 — must NOT see tenant from job 1.
    $middleware->handle($job2, fn ($j) => $j->handle());

    expect($job2->seenTenantId)->toBeNull();
});

// ─── Failure when tenant deleted after dispatch ───────────────────────────────

test('job fails clearly when tenant is soft-deleted after dispatch', function (): void {
    $tenant = Tenant::factory()->create();
    app(TenantContextContract::class)->setTenant($tenant);

    $job = new class {
        use HasTenantContext;

        public function __construct()
        {
            $this->captureTenantContext();
        }

        public function handle(): void {}
    };

    // Soft-delete the tenant after the job was dispatched.
    $tenant->delete();
    app(TenantContextContract::class)->clear();

    expect(fn () => (new RestoreTenantContext())->handle($job, fn ($j) => $j->handle()))
        ->toThrow(\RuntimeException::class, 'RestoreTenantContext');
});

// ─── Platform-level jobs (no tenant context) ─────────────────────────────────

test('platform job without tenant id runs without context', function (): void {
    // Job with tenantId = null runs without tenant context — no exception thrown.
    $job = new class {
        use HasTenantContext;

        public bool $ran = false;

        public function handle(): void
        {
            $this->ran = true;
        }
    };
    // tenantId remains null — captureTenantContext() was not called.

    (new RestoreTenantContext())->handle($job, fn ($j) => $j->handle());

    expect($job->ran)->toBeTrue()
        ->and(app(TenantContextContract::class)->isResolved())->toBeFalse();
});
