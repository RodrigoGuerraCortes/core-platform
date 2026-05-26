<?php

declare(strict_types=1);

namespace Database\Seeders\CondoFlow;

use App\Core\CondoFlow\Enums\TicketPriority;
use App\Core\CondoFlow\Enums\TicketStatus;
use App\Core\CondoFlow\Enums\ResidentStatus;
use App\Core\CondoFlow\Enums\UnitStatus;
use App\Core\CondoFlow\Models\Building;
use App\Core\CondoFlow\Models\MaintenanceTicket;
use App\Core\CondoFlow\Models\Resident;
use App\Core\CondoFlow\Models\Unit;
use App\Core\Tenancy\Contracts\TenantContextContract;
use App\Core\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * CondoFlow vertical bootstrap — realistic development data.
 *
 * Creates a CondoFlow-specific tenant (vista-mar) with:
 *  - 2 buildings (Torre A, Torre B)
 *  - 5 units across both buildings
 *  - 3 users (admin + 2 residents)
 *  - 3 residents linked to units
 *  - 4 maintenance tickets in various statuses
 *
 * Also seeds CondoFlow data into the Acme tenant for cross-experience testing.
 * Idempotent: safe to re-run.
 */
class CondoFlowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenant('vista-mar', 'Condominio Vista Mar');
        $this->seedTenant('acme', 'Acme');

        $this->command->info('[CondoFlowSeeder] CondoFlow development data ready.');
    }

    private function seedTenant(string $slug, string $name): void
    {
        $tenant = Tenant::firstOrCreate(['slug' => $slug], ['name' => $name]);
        $tenantId = $tenant->id;

        // Set tenant context so BelongsToTenant models can be queried/created
        app(TenantContextContract::class)->setTenant($tenant);

        // ── Users ────────────────────────────────────────────────────────────
        $password = Hash::make(env('DEFAULT_ADMIN_PASSWORD', 'ChangeMe123!'));

        $admin = User::firstOrCreate(
            ['email' => "admin@{$slug}.test"],
            [
                'name' => 'Admin Condo',
                'password' => $password,
                'email_verified_at' => now(),
                'is_platform_admin' => false,
            ],
        );
        $tenant->users()->syncWithoutDetaching([$admin->id => ['membership_role' => 'admin']]);

        $resident1 = User::firstOrCreate(
            ['email' => "resident1@{$slug}.test"],
            [
                'name' => 'María González',
                'password' => $password,
                'email_verified_at' => now(),
                'is_platform_admin' => false,
            ],
        );
        $tenant->users()->syncWithoutDetaching([$resident1->id => ['membership_role' => 'member']]);

        $resident2 = User::firstOrCreate(
            ['email' => "resident2@{$slug}.test"],
            [
                'name' => 'Carlos Muñoz',
                'password' => $password,
                'email_verified_at' => now(),
                'is_platform_admin' => false,
            ],
        );
        $tenant->users()->syncWithoutDetaching([$resident2->id => ['membership_role' => 'member']]);

        // ── Buildings ────────────────────────────────────────────────────────
        $torreA = Building::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Torre A'],
            ['address' => 'Av. Del Mar 1200', 'floors' => 10],
        );

        $torreB = Building::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Torre B'],
            ['address' => 'Av. Del Mar 1202', 'floors' => 8],
        );

        // ── Units ────────────────────────────────────────────────────────────
        $unit101 = Unit::firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $torreA->id, 'number' => '101'],
            ['floor' => 1, 'status' => UnitStatus::Occupied],
        );

        $unit102 = Unit::firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $torreA->id, 'number' => '102'],
            ['floor' => 1, 'status' => UnitStatus::Occupied],
        );

        Unit::firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $torreA->id, 'number' => '103'],
            ['floor' => 1, 'status' => UnitStatus::Available],
        );

        Unit::firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $torreB->id, 'number' => '201'],
            ['floor' => 2, 'status' => UnitStatus::Occupied],
        );

        Unit::firstOrCreate(
            ['tenant_id' => $tenantId, 'building_id' => $torreB->id, 'number' => '202'],
            ['floor' => 2, 'status' => UnitStatus::Available],
        );

        // ── Residents ────────────────────────────────────────────────────────
        $residentMaria = Resident::firstOrCreate(
            ['tenant_id' => $tenantId, 'email' => $resident1->email],
            [
                'name' => $resident1->name,
                'phone' => '+56 9 1234 5678',
                'rut' => '12.345.678-9',
                'unit_id' => $unit101->id,
                'status' => ResidentStatus::Active,
            ],
        );

        $residentCarlos = Resident::firstOrCreate(
            ['tenant_id' => $tenantId, 'email' => $resident2->email],
            [
                'name' => $resident2->name,
                'phone' => '+56 9 8765 4321',
                'rut' => '9.876.543-2',
                'unit_id' => $unit102->id,
                'status' => ResidentStatus::Active,
            ],
        );

        // ── Maintenance Tickets ──────────────────────────────────────────────
        MaintenanceTicket::firstOrCreate(
            ['tenant_id' => $tenantId, 'title' => 'Ascensor roto en Torre A'],
            [
                'description' => 'El ascensor del piso 1 al 5 no funciona desde ayer. Quedó detenido entre piso 3 y 4.',
                'unit_id' => $unit101->id,
                'resident_id' => $residentMaria->id,
                'status' => TicketStatus::Open,
                'priority' => TicketPriority::High,
            ],
        );

        MaintenanceTicket::firstOrCreate(
            ['tenant_id' => $tenantId, 'title' => 'Fuga de agua en baño'],
            [
                'description' => 'Hay una filtración constante en el cielo del baño. Parece venir del depto de arriba.',
                'unit_id' => $unit102->id,
                'resident_id' => $residentCarlos->id,
                'status' => TicketStatus::InProgress,
                'priority' => TicketPriority::High,
            ],
        );

        MaintenanceTicket::firstOrCreate(
            ['tenant_id' => $tenantId, 'title' => 'Ruido excesivo en las noches'],
            [
                'description' => 'Vecino del 201 hace fiestas hasta las 3am los fines de semana.',
                'unit_id' => $unit101->id,
                'resident_id' => $residentMaria->id,
                'status' => TicketStatus::Open,
                'priority' => TicketPriority::Medium,
            ],
        );

        MaintenanceTicket::firstOrCreate(
            ['tenant_id' => $tenantId, 'title' => 'Iluminación estacionamiento'],
            [
                'description' => 'Las luces del estacionamiento subterráneo llevan 2 semanas apagadas. Peligro de seguridad.',
                'unit_id' => $unit102->id,
                'resident_id' => $residentCarlos->id,
                'status' => TicketStatus::Resolved,
                'priority' => TicketPriority::Low,
            ],
        );
    }
}
