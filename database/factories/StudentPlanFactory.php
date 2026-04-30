<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentPlanFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->toDateString();
        $end = now()->addMonths(1)->toDateString();

        return [
            'student_id' => Student::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'class_quota' => '8',
            'classes_remaining' => 8,
            'price' => 120.00,
            'promotion' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'classes_remaining' => 8,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(35)->toDateString(),
        ]);
    }
}
