<?php

declare(strict_types=1);

namespace App\Core\Shared\Http\Middleware;

use App\Core\Tenancy\Contracts\TenantContextContract;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pushes structured context into every log entry for the current request.
 *
 * After this middleware runs, ALL Log::* calls automatically include:
 *   - request_id
 *   - tenant_slug (if resolved)
 *   - user_id (if authenticated)
 *   - http_method
 *   - http_path
 *
 * This is the canonical way to enrich logs — modules do NOT manually
 * attach context. They just call Log::info('message') and context is there.
 */
class PushLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = [
            'request_id' => $request->attributes->get('request_id', 'unknown'),
            'http_method' => $request->method(),
            'http_path' => $request->path(),
            'ip' => $request->ip(),
        ];

        // Tenant context (may not be resolved yet at this point for some routes)
        $tenantContext = app(TenantContextContract::class);
        if ($tenantContext->isResolved()) {
            $context['tenant_slug'] = $tenantContext->tenant()?->slug;
            $context['tenant_id'] = $tenantContext->tenantId();
        }

        // Auth (populated after auth middleware; we use a terminating approach)
        if ($userId = auth()->id()) {
            $context['user_id'] = $userId;
        }

        Log::shareContext($context);

        /** @var Response $response */
        $response = $next($request);

        // Enrich with user_id if auth resolved during the request
        if (!isset($context['user_id']) && $userId = auth()->id()) {
            Log::shareContext(['user_id' => $userId]);
        }

        // Enrich with tenant if resolved after middleware stack
        if (!isset($context['tenant_slug']) && $tenantContext->isResolved()) {
            Log::shareContext([
                'tenant_slug' => $tenantContext->tenant()?->slug,
                'tenant_id' => $tenantContext->tenantId(),
            ]);
        }

        return $response;
    }
}
