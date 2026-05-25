<?php

namespace Database\Seeders;

use Database\Seeders\Development\DevelopmentBootstrapSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production: ships with zero tenant data — tenants are created via the
     * admin panel or platform tooling after deployment.
     *
     * Development / local: DevelopmentBootstrapSeeder guarantees a usable
     * platform state immediately after `php artisan db:seed`. This includes
     * the Acme tenant, admin users, and owner memberships so that
     * /t/acme/* routes are operational without manual Tinker work.
     */
    public function run(): void
    {
        if (! App::environment('production')) {
            $this->call(DevelopmentBootstrapSeeder::class);
        }
    }
}
