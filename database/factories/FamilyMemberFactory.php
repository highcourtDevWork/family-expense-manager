<?php

namespace Database\Factories;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMember>
 */
class FamilyMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
            'role' => FamilyRole::Member,
            'relationship' => fake()->randomElement([
                Relationship::Father,
                Relationship::Mother,
                Relationship::Son,
                Relationship::Daughter,
            ]),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => FamilyRole::Admin,
        ]);
    }
}
