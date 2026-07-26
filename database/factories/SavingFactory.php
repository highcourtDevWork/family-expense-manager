<?php

namespace Database\Factories;

use App\Enums\SavingType;
use App\Models\Family;
use App\Models\Saving;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Saving>
 */
class SavingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'saving_type' => fake()->randomElement(SavingType::cases()),
            'amount' => fake()->randomFloat(2, 500, 200000),
            'notes' => fake()->optional()->sentence(3),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
