<?php

declare(strict_types=1);

namespace Database\Factories\CondoFlow;

use App\Core\CondoFlow\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Unit> */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->numerify('###'),
            'floor' => $this->faker->numberBetween(1, 20),
            'type' => 'apartment',
            'status' => 'available',
            'metadata' => null,
        ];
    }
}
