<?php

declare(strict_types=1);

namespace Database\Factories\CondoFlow;

use App\Core\CondoFlow\Models\MaintenanceTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceTicket> */
class MaintenanceTicketFactory extends Factory
{
    protected $model = MaintenanceTicket::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status' => 'open',
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'metadata' => null,
        ];
    }
}
