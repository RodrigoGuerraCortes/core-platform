<?php

declare(strict_types=1);

namespace App\Core\Shared\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Queue diagnostics — inspect failed jobs for support and operations.
 *
 * Usage:
 *   php artisan queue:diagnostics              — summary of failed jobs
 *   php artisan queue:diagnostics --recent=10  — show last 10 failures
 *   php artisan queue:diagnostics --tenant=acme — filter by tenant slug
 *
 * This is a READ-ONLY inspection tool. It does NOT retry or delete jobs.
 * Use Laravel's built-in queue:retry / queue:flush for mutations.
 */
class QueueDiagnosticsCommand extends Command
{
    protected $signature = 'queue:diagnostics
                            {--recent=5 : Number of recent failures to display}
                            {--tenant= : Filter by tenant slug in payload}';

    protected $description = 'Inspect failed jobs for operational diagnostics.';

    public function handle(): int
    {
        $this->newLine();
        $this->info('  Queue Diagnostics');
        $this->info('  ' . str_repeat('─', 50));

        // Summary
        try {
            $totalFailed = DB::table('failed_jobs')->count();
            $this->newLine();
            $this->line("  Total failed jobs: <comment>{$totalFailed}</comment>");

            if ($totalFailed === 0) {
                $this->newLine();
                $this->info('  ✓ No failed jobs. Queue is healthy.');
                $this->newLine();
                return self::SUCCESS;
            }

            // Group by job class
            $byClass = DB::table('failed_jobs')
                ->selectRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(payload, '\"displayName\":\"', -1), '\"', 1) as job_class, COUNT(*) as count")
                ->groupByRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(payload, '\"displayName\":\"', -1), '\"', 1)")
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            if ($byClass->isNotEmpty()) {
                $this->newLine();
                $this->line('  <info>Failures by job class:</info>');
                $this->table(
                    ['Job Class', 'Count'],
                    $byClass->map(fn ($r) => [$r->job_class, $r->count])->toArray(),
                );
            }

            // Recent failures
            $recent = (int) $this->option('recent');
            $query = DB::table('failed_jobs')->orderByDesc('failed_at')->limit($recent);

            if ($tenant = $this->option('tenant')) {
                $query->where('payload', 'like', "%{$tenant}%");
            }

            $failures = $query->get();

            $this->newLine();
            $this->line("  <info>Last {$recent} failures:</info>");
            $this->table(
                ['ID', 'Job', 'Failed At', 'Exception (truncated)'],
                $failures->map(function ($f) {
                    $payload = json_decode($f->payload, true);
                    $jobName = class_basename($payload['displayName'] ?? 'Unknown');
                    $exception = substr($f->exception, 0, 80);
                    return [$f->id, $jobName, $f->failed_at, $exception . '...'];
                })->toArray(),
            );
        } catch (\Throwable $e) {
            $this->error("  Could not query failed_jobs: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <comment>Tip:</comment> Use <info>php artisan queue:retry {id}</info> to retry a specific job.');
        $this->line('  <comment>Tip:</comment> Use <info>php artisan queue:flush</info> to clear all failed jobs.');
        $this->newLine();

        return self::SUCCESS;
    }
}
