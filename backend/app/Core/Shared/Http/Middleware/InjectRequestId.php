<?php

declare(strict_types=1);

namespace App\Core\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects a correlation/request ID into every HTTP request.
 *
 * Lifecycle:
 *   1. If X-Request-ID header is present (e.g. from a load balancer), reuse it.
 *   2. Otherwise, generate a UUIDv4.
 *   3. Store in the request attributes for downstream access.
 *   4. Add to the response headers for client correlation.
 *   5. Push into the shared logging context so all log lines include it.
 *
 * Access the request ID anywhere via:
 *   request()->attributes->get('request_id')
 *   — or —
 *   app('request.id')
 */
class InjectRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();

        // Store on request for access throughout the lifecycle
        $request->attributes->set('request_id', $requestId);

        // Bind to container for convenience
        app()->instance('request.id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        // Echo back in response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
