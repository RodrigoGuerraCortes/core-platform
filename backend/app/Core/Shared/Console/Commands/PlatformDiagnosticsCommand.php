<?php

declare(strict_types=1);

namespace App\Core\Shared\Console\Commands;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Platform diagnostics — comprehensive runtime health inspection.
 *
 * Purpose:
 *   - Support tool for debugging production issues
 *   - CI sanity check after deployment
 *   - Developer onboarding validation
 *
 * Usage:
 *   php artisan platform:diagnostics
 *   php artisan platform:diagnostics --json
 *
 * Exit codes:
 *   0 — all checks passed
 *   1 — one or more critical failures
 */
class PlatformDiagnosticsCommand extends Command
{
    protected $signature = 'platform:diagnostics
                            {--json : Output as JSON instead of table}';

    protected $description = 'Run comprehensive platform health diagnostics.';

    public function handle(): int
    {
        $results = [];
        $hasCritical = false;

        // ─── Environment ──────────────────────────────────────────────────────
        $results[] = $this->check('Environment', fn () => app()->environment(), true);
        $results[] = $this->check('PHP version', fn () => PHP_VERSION, version_compare(PHP_VERSION, '8.3.0', '>='));
        $results[] = $this->check('App debug', fn () => config('app.debug') ? 'ON' : 'OFF', true);
        $results[] = $this->check('App URL', fn () => config('app.url') ?: 'NOT SET', (bool) config('app.url'));

        // ─── Database ─────────────────────────────────────────────────────────
        try {
            DB::connection()->getPdo();
            $dbVersion = DB::scalar('SELECT version()');
            $results[] = ['Database', "Connected ({$dbVersion})", '✓', 'ok'];
        } catch (\Throwable $e) {
            $results[] = ['Database', $e->getMessage(), '✗', 'critical'];
            $hasCritical = true;
        }

        // ─── Cache ────────────────────────────────────────────────────────────
        try {
            $key = '_diag_' . time();
            Cache::put($key, 'ok', 5);
            $val = Cache::get($key);
            Cache::forget($key);
            $results[] = ['Cache (' . config('cache.default') . ')', $val === 'ok' ? 'Read/Write OK' : 'Read failed', $val === 'ok' ? '✓' : '✗', $val === 'ok' ? 'ok' : 'critical'];
            if ($val !== 'ok') {
                $hasCritical = true;
            }
        } catch (\Throwable $e) {
            $results[] = ['Cache', $e->getMessage(), '✗', 'critical'];
            $hasCritical = true;
        }

        // ─── Queue ────────────────────────────────────────────────────────────
        try {
            $driver = config('queue.default');
            if ($driver === 'database') {
                $pending = DB::table(config('queue.connections.database.table', 'jobs'))->count();
                $results[] = ['Queue (database)', "{$pending} pending jobs", '✓', 'ok'];
            } else {
                $results[] = ['Queue', "Driver: {$driver}", '✓', 'ok'];
            }
        } catch (\Throwable $e) {
            $results[] = ['Queue', $e->getMessage(), '✗', 'critical'];
            $hasCritical = true;
        }

        // ─── Storage ──────────────────────────────────────────────────────────
        try {
            $testFile = '_diag_' . time() . '.tmp';
            Storage::put($testFile, 'ok');
            $read = Storage::get($testFile);
            Storage::delete($testFile);
            $results[] = ['Storage (local)', $read === 'ok' ? 'Read/Write OK' : 'Write failed', $read === 'ok' ? '✓' : '✗', $read === 'ok' ? 'ok' : 'critical'];
            if ($read !== 'ok') {
                $hasCritical = true;
            }
        } catch (\Throwable $e) {
            $results[] = ['Storage', $e->getMessage(), '✗', 'critical'];
            $hasCritical = true;
        }

        // ─── Writable directories ────────────────────────────────────────────
        $dirs = ['storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'bootstrap/cache'];
        foreach ($dirs as $dir) {
            $path = base_path($dir);
            $writable = is_writable($path);
            $results[] = ["Writable: {$dir}", $writable ? 'OK' : 'NOT WRITABLE', $writable ? '✓' : '!', $writable ? 'ok' : 'warning'];
        }

        // ─── Tenant bootstrap ─────────────────────────────────────────────────
        try {
            $tenantCount = Tenant::count();
            $results[] = ['Tenants', "{$tenantCount} registered", $tenantCount > 0 ? '✓' : '!', $tenantCount > 0 ? 'ok' : 'warning'];
        } catch (\Throwable) {
            $results[] = ['Tenants', 'Table not found', '!', 'warning'];
        }

        // ─── Orphan users ─────────────────────────────────────────────────────
        try {
            $orphans = User::whereDoesntHave('tenants')->count();
            $results[] = ['Orphan users', "{$orphans} without tenant", $orphans === 0 ? '✓' : '!', $orphans === 0 ? 'ok' : 'warning'];
        } catch (\Throwable) {
            $results[] = ['Orphan users', 'Could not query', '?', 'warning'];
        }

        // ─── Failed jobs ──────────────────────────────────────────────────────
        try {
            $failedCount = DB::table('failed_jobs')->count();
            $results[] = ['Failed jobs', (string) $failedCount, $failedCount === 0 ? '✓' : '!', $failedCount === 0 ? 'ok' : 'warning'];
        } catch (\Throwable) {
            $results[] = ['Failed jobs', 'Table not found', '?', 'info'];
        }

        // ─── Output ──────────────────────────────────────────────────────────
        if ($this->option('json')) {
            $this->line(json_encode([
                'healthy' => !$hasCritical,
                'checks' => array_map(fn ($r) => [
                    'name' => $r[0],
                    'detail' => $r[1],
                    'severity' => $r[3],
                ], $results),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->newLine();
            $this->info('  Platform Diagnostics');
            $this->info('  ' . str_repeat('─', 50));
            $this->newLine();

            $this->table(
                ['Check', 'Result', ''],
                array_map(fn ($r) => [$r[0], $r[1], $r[2]], $results),
            );

            $this->newLine();
            if ($hasCritical) {
                $this->error('  ✗ One or more CRITICAL checks failed.');
            } else {
                $this->info('  ✓ All critical checks passed.');
            }
            $this->newLine();
        }

        return $hasCritical ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $name, callable $fn, bool $pass): array
    {
        $value = $fn();
        return [$name, (string) $value, $pass ? '✓' : '!', $pass ? 'ok' : 'warning'];
    }
}
