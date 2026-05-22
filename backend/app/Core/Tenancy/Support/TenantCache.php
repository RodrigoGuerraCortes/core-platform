<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Support;

use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Exceptions\TenantContextNotResolvedException;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant-isolated cache helper.
 *
 * Prefixes all cache keys with the current tenant ID to prevent
 * cross-tenant cache contamination.
 *
 * Key format:
 *   tenant:{tenantId}:{key}
 *
 * Example:
 *   tenant:42:settings   → settings for tenant 42
 *   tenant:17:reports    → reports cache for tenant 17
 *
 * Usage:
 *   $cache = app(TenantCache::class);
 *   $cache->put('settings', $data, now()->addHour());
 *   $cache->get('settings');
 *   $cache->forget('settings');
 *   $cache->remember('settings', 3600, fn () => loadSettings());
 *
 * For global (platform-wide) cache that should NOT be tenant-isolated,
 * use the Cache facade directly:
 *   Cache::put('platform:config', $data);
 *
 * Throws TenantContextNotResolvedException if no tenant context is resolved.
 * This prevents accidental global cache pollution when tenant context is missing.
 *
 * ⚠️  ASYNC WARNING — In queue workers, TenantContext must be explicitly
 * restored via RestoreTenantContext job middleware before using TenantCache.
 */
class TenantCache
{
    public function __construct(private readonly TenantContextContract $context) {}

    /**
     * Builds a tenant-scoped cache key.
     * Throws if no tenant context is resolved.
     */
    public function key(string $key): string
    {
        $tenantId = $this->context->tenantId();

        if ($tenantId === null) {
            throw new TenantContextNotResolvedException();
        }

        return "tenant:{$tenantId}:{$key}";
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->key($key), $default);
    }

    public function put(string $key, mixed $value, \DateTimeInterface|\DateInterval|int|null $ttl = null): bool
    {
        return Cache::put($this->key($key), $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($this->key($key));
    }

    public function remember(string $key, \DateTimeInterface|\DateInterval|int $ttl, \Closure $callback): mixed
    {
        return Cache::remember($this->key($key), $ttl, $callback);
    }
}
