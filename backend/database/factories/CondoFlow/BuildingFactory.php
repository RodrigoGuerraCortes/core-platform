<?php

declare(strict_types=1);

namespace Database\Factories\CondoFlow;

use App\Core\CondoFlow\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Building> */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'name' => 'Edificio ' . $this->faker->lastName(),
            'address' => $this->faker->streetAddress(),
            'floors' => $this->faker->numberBetween(1, 30),
            'metadata' => null,
        ];
    }
}
