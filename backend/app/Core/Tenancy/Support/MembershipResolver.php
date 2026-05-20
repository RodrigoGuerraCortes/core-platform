<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Support;

use App\Models\User;

/**
 * Request-scoped membership role resolver.
 *
 * Caches tenant_user.membership_role lookups within a single HTTP request lifecycle.
 * Prevents repeated database queries when a policy checks the same user+tenant
 * combination multiple times (e.g. viewAny + view in the same request).
 *
 * Cache key: "{userId}:{tenantId}"
 * Cache scope: this request only — reset automatically at the start of the next request
 *              because MembershipResolver is bound as scoped() in TenancyServiceProvider.
 *
 * This is NOT a persistent cache. Values are never written to Redis, Memcached, or the
 * database cache. They exist only in this instance's array for the current request.
 *
 * ⚠️  ASYNC WARNING — Queue workers get a fresh MembershipResolver per job (scoped binding
 * is reset between jobs by RestoreTenantContext). No cross-job leakage is possible.
 */
class MembershipResolver
{
    /** @var array<string, string|null> */
    private array $cache = [];

    /**
     * Return the membership_role for the given user in the given tenant.
     *
     * Returns null when the user is not a member of the tenant.
     * Result is cached for the duration of the request.
     */
    public function roleFor(User $user, int $tenantId): ?string
    {
        $key = "{$user->id}:{$tenantId}";

        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $user->tenants()
                ->where('tenants.id', $tenantId)
                ->first()
                ?->pivot
                ?->membership_role;
        }

        return $this->cache[$key];
    }

    /**
     * Flush the in-memory cache.
     *
     * Not required in normal request flow. Exposed for testing and for any
     * future tooling that needs to invalidate the cache mid-request.
     */
    public function flush(): void
    {
        $this->cache = [];
    }
}
