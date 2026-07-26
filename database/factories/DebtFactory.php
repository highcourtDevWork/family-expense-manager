<?php

namespace Database\Factories;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Models\Debt;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 100000);

        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'person_name' => fake()->name(),
            'debt_type' => fake()->randomElement(DebtType::cases()),
            'amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(4),
            'status' => DebtStatus::Open,
        ];
    }
}
