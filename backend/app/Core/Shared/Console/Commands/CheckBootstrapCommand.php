<?php

declare(strict_types=1);

namespace App\Core\Shared\Console\Commands;

use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Platform bootstrap health check.
 *
 * Warns developers when the platform is in a non-operational state:
 * zero tenants, zero memberships, or users with no tenant access.
 *
 * Designed for development onboarding. Does NOT throw or crash.
 * Safe to call from setup.sh.
 *
 * Usage:
 *   php artisan platform:check-bootstrap
 *   php artisan platform:check-bootstrap --fix   (runs db:seed automatically)
 */
class CheckBootstrapCommand extends Command
{
    protected $signature = 'platform:check-bootstrap
                            {--fix : Run db:seed to resolve missing bootstrap state}';

    protected $description = 'Warn if the platform is missing required bootstrap data (tenants, memberships).';

    public function handle(): int
    {
        $tenantCount     = Tenant::count();
        $userCount       = User::count();
        $membershipCount = DB::table('tenant_user')->count();
        $orphanCount     = User::whereDoesntHave('tenants')->count();

        $healthy = true;

        $this->line('');
        $this->line('<fg=cyan>Platform Bootstrap Check</>');
        $this->line(str_repeat('─', 40));

        $this->check("Tenants in database ({$tenantCount})", $tenantCount > 0, $healthy);
        $this->check("Users in database ({$userCount})", $userCount > 0, $healthy);
        $this->check("Memberships in database ({$membershipCount})", $membershipCount > 0, $healthy);

        if ($orphanCount > 0) {
            $this->warn("  ⚠  {$orphanCount} user(s) have no tenant membership — they cannot access any tenant routes.");
            $healthy = false;
        }

        $this->line(str_repeat('─', 40));

        if ($healthy) {
            $this->info('✓  Platform bootstrap is healthy.');
            $this->line('');

            return self::SUCCESS;
        }

        $this->error('✗  Platform bootstrap is incomplete.');
        $this->line('');
        $this->line('  Run:  <fg=yellow>make fresh</>  or  <fg=yellow>php artisan db:seed</>');
        $this->line('  Or:   <fg=yellow>php artisan platform:check-bootstrap --fix</>');
        $this->line('');

        if ($this->option('fix')) {
            $this->line('Running db:seed to restore bootstrap state...');
            $this->call('db:seed');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function check(string $label, bool $ok, bool &$healthy): void
    {
        if ($ok) {
            $this->line("  <fg=green>✓</>  {$label}");
        } else {
            $this->line("  <fg=red>✗</>  {$label}");
            $healthy = false;
        }
    }
}

