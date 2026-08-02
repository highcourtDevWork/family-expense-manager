<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Family;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'expense_category_id' => null,
            'name' => fake()->randomElement(['House Rent', 'Internet Bill', 'Electricity', 'Gym']),
            'amount' => fake()->randomFloat(2, 500, 20000),
            'payment_method' => PaymentMethod::Cash,
            'auto_post' => false,
            'active' => true,
        ];
    }
}
