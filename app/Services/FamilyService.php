<?php

namespace App\Services;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FamilyService
{
    /**
     * The categories every new family starts with.
     *
     * @var list<string>
     */
    public const DEFAULT_CATEGORIES = [
        'Food',
        'Travel',
        'Medical',
        'Education',
        'House Rent',
        'Electricity',
        'Internet',
        'Shopping',
        'Entertainment',
        'Others',
    ];

    /**
     * The income sources every new family starts with.
     *
     * @var list<string>
     */
    public const DEFAULT_INCOME_SOURCES = [
        'Salary',
        'Business Income',
        'Rental Income',
        'Other',
    ];

    /**
     * Create a family owned by the given user, register them as its admin,
     * and seed the default expense categories — all atomically.
     */
    public function createForUser(User $user, ?string $name = null): Family
    {
        return DB::transaction(function () use ($user, $name): Family {
            $family = Family::create([
                'name' => $name ?: $user->name."'s Family",
                'created_by' => $user->id,
            ]);

            $family->members()->create([
                'user_id' => $user->id,
                'role' => FamilyRole::Admin,
                'relationship' => Relationship::Head,
            ]);

            $this->seedDefaultCategories($family);
            $this->seedDefaultIncomeSources($family);

            return $family;
        });
    }

    /**
     * Insert the default categories for a freshly created family.
     */
    protected function seedDefaultCategories(Family $family): void
    {
        $now = now();

        $rows = array_map(static fn (string $name): array => [
            'family_id' => $family->id,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::DEFAULT_CATEGORIES);

        $family->categories()->insert($rows);
    }

    /**
     * Insert the default income sources for a freshly created family.
     */
    protected function seedDefaultIncomeSources(Family $family): void
    {
        $now = now();

        $rows = array_map(static fn (string $name): array => [
            'family_id' => $family->id,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::DEFAULT_INCOME_SOURCES);

        $family->incomeSources()->insert($rows);
    }
}
