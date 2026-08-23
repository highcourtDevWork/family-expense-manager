<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringIncome>
 */
class RecurringIncomeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'income_source_id' => null,
            'name' => fake()->randomElement(['Salary', 'Rental Income', 'Pension']),
            'amount' => fake()->randomFloat(2, 10000, 150000),
            'auto_post' => false,
            'active' => true,
        ];
    }
}
