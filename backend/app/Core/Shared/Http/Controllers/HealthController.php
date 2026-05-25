<?php

declare(strict_types=1);

namespace App\Core\Shared\Http\Controllers;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Health check endpoints for uptime monitoring and deployment validation.
 *
 * Routes:
 *   GET /health          → simple OK (for load balancers, uptime monitors)
 *   GET /health/detailed → comprehensive check (non-prod or platform admin)
 *
 * /up is already registered by Laravel's bootstrap (returns 200 OK by default).
 */
class HealthController extends Controller
{
    /**
     * Simple health check — always fast, no dependencies.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detailed health check — validates all infrastructure dependencies.
     *
     * Access control:
     *   - Non-production: always accessible
     *   - Production: requires authenticated platform admin
     */
    public function detailed(Request $request): JsonResponse
    {
        // Gate: production requires platform admin
        if (app()->environment('production')) {
            $user = $request->user();
            if (!$user || !($user->is_platform_admin ?? false)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $checks = [];
        $healthy = true;

        // Database
        try {
            DB::connection()->getPdo();
            $dbVersion = DB::scalar('SELECT version()');
            $checks['database'] = ['status' => 'ok', 'version' => $dbVersion];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Cache
        try {
            $cacheKey = '_health_check_' . time();
            Cache::put($cacheKey, 'ok', 10);
            $cacheValue = Cache::get($cacheKey);
            Cache::forget($cacheKey);
            $checks['cache'] = [
                'status' => $cacheValue === 'ok' ? 'ok' : 'fail',
                'driver' => config('cache.default'),
            ];
            if ($cacheValue !== 'ok') {
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Queue
        try {
            $queueDriver = config('queue.default');
            if ($queueDriver === 'database') {
                DB::table(config('queue.connections.database.table', 'jobs'))->count();
            }
            $checks['queue'] = ['status' => 'ok', 'driver' => $queueDriver];
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Storage
        try {
            $testFile = '_health_check_' . time() . '.txt';
            Storage::put($testFile, 'ok');
            $written = Storage::get($testFile);
            Storage::delete($testFile);
            $checks['storage'] = [
                'status' => $written === 'ok' ? 'ok' : 'fail',
                'disk' => config('filesystems.default'),
            ];
            if ($written !== 'ok') {
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['storage'] = ['status' => 'fail', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Platform stats (non-critical)
        try {
            $checks['platform'] = [
                'tenants' => Tenant::count(),
                'users' => User::count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Throwable) {
            $checks['platform'] = ['status' => 'unavailable'];
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
