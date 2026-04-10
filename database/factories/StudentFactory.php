<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'   => $this->faker->name(),
            'dni'    => $this->faker->optional()->numerify('########'),
            'phone'  => $this->faker->optional()->numerify('9########'),
            'notes'  => null,
            'active' => true,
        ];
    }
}
