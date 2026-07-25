<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Models\Family;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 50000, 2000000);

        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'loan_name' => fake()->randomElement(['Home Loan', 'Car Loan', 'Personal Loan']),
            'loan_type' => fake()->randomElement(LoanType::cases()),
            'total_amount' => $total,
            'paid_amount' => 0,
            'remaining_amount' => $total,
            'start_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
            'status' => LoanStatus::Active,
        ];
    }
}
