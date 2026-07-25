<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
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
            'income_source_id' => IncomeSource::factory()->for($family),
            'income_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 1000, 80000),
            'notes' => fake()->optional()->sentence(4),
        ];
    }
}
