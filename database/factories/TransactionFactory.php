<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Faculty;
use App\Models\User;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_number' => 'TXN-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4)),
            'faculty_id' => Faculty::factory(),
            'recorded_by' => User::factory()->state(['role' => 'cashier']),
            'transaction_date' => now(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => TransactionStatus::ACTIVE->value,
            'credit_limit_override' => false,
            'override_reason' => null,
            'override_authorized_by' => null,
            'payroll_deduction_id' => null,
        ];
    }
}