<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanPayment>
 */
class LoanPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'payment_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'notes' => fake()->optional()->sentence(3),
        ];
    }
}
