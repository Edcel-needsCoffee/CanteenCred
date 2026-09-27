<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use App\Enums\PayrollPeriodStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'Period ' . $this->faker->word . ' ' . $this->faker->year,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'status' => PayrollPeriodStatus::CLOSED->value,
        ];
    }
}