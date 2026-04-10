<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'schedule'    => [
                'lun' => ['start' => '18:00', 'end' => '19:30'],
                'mie' => ['start' => '18:00', 'end' => '19:30'],
            ],
            'active' => true,
        ];
    }
}
