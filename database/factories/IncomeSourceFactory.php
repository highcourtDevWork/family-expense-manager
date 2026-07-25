<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\IncomeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeSource>
 */
class IncomeSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->randomElement([
                'Salary', 'Business Income', 'Rental Income', 'Freelance', 'Interest', 'Dividend',
            ]),
        ];
    }
}
