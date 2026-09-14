<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\SplitExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SplitExpense>
 */
class SplitExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'paid_by' => User::factory(),
            'created_by' => User::factory(),
            'expense_category_id' => null,
            'title' => fake()->randomElement(['Room Rent', 'Electricity', 'Internet', 'Groceries', 'Gas']),
            'total_amount' => fake()->randomFloat(2, 1000, 40000),
            'period' => now()->startOfMonth(),
            'notes' => null,
        ];
    }
}
