<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Database\Seeders\CondoFlow\CondoFlowSeeder;
use Database\Seeders\Core\CoreSeeder;
use Illuminate\Database\Seeder;

/**
 * Development bootstrap orchestrator.
 *
 * Calls domain seeders in dependency order to produce a fully operational
 * local development environment. Each domain seeder owns its own data.
 *
 * Flow:
 *   CoreSeeder        → platform admin, Acme tenant, memberships
 *   CondoFlowSeeder   → buildings, units, residents, tickets (vista-mar + acme)
 *   (future)          → MiniHisSeeder, ERPSeeder, etc.
 *
 * NEVER run in production (DatabaseSeeder gates on environment).
 * Idempotent: all sub-seeders use firstOrCreate.
 */
class DevelopmentBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreSeeder::class,
            CondoFlowSeeder::class,
            // Future: MiniHisSeeder::class,
        ]);
    }
}
