<?php

declare(strict_types=1);

namespace Database\Factories\CondoFlow;

use App\Core\CondoFlow\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Resident> */
class ResidentFactory extends Factory
{
    protected $model = Resident::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'rut' => $this->faker->optional()->numerify('##.###.###-#'),
            'email' => $this->faker->optional()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'status' => 'active',
            'metadata' => null,
        ];
    }
}
