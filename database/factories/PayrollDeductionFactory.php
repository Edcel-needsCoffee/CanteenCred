<?php

namespace Database\Factories;

use App\Models\PayrollDeduction;
use App\Models\PayrollPeriod;
use App\Models\Faculty;
use App\Enums\PayrollDeductionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollDeduction>
 */
class PayrollDeductionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'faculty_id' => Faculty::factory(),
            'total_amount' => $this->faker->randomFloat(2, 10, 5000),
            'status' => PayrollDeductionStatus::APPROVED->value,
            'generated_at' => now(),
            'approved_at' => now(),
        ];
    }
}