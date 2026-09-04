<?php

namespace Database\Factories;

use App\Enums\SavingType;
use App\Models\Family;
use App\Models\RecurringSaving;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringSaving>
 */
class RecurringSavingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Monthly SIP', 'Recurring Deposit', 'Gold Savings']),
            'saving_type' => fake()->randomElement(SavingType::cases()),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'auto_post' => false,
            'active' => true,
        ];
    }
}
