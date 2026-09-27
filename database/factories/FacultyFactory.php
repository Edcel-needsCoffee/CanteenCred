<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faculty_number' => 'FAC-' . Str::upper(Str::random(8)),
            'name' => fake()->name(),
            'department' => fake()->randomElement(['College of IT', 'College of Engineering', 'College of Business', 'College of Arts', 'College of Science']),
            'credit_limit' => fake()->randomFloat(2, 1000, 5000),
            'is_active' => true,
        ];
    }
}