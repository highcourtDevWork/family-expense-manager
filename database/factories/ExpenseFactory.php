<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $family = Family::factory();

        return [
            'family_id' => $family,
            'user_id' => User::factory(),
            'expense_category_id' => ExpenseCategory::factory()->for($family),
            'expense_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'description' => fake()->optional()->sentence(4),
            'attachment_path' => null,
        ];
    }
}
