<?php

namespace Tests\Feature\Settings;

use App\Models\Expense;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_add_a_category(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Fuel'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('expense_categories', ['family_id' => $family->id, 'name' => 'Fuel']);
    }

    public function test_category_names_are_unique_within_a_family(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user); // seeds "Food"

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'Food'])
            ->assertSessionHasErrors('name');
    }

    public function test_a_member_can_rename_a_category(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        $category = $family->categories()->firstWhere('name', 'Food');

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'Groceries'])
            ->assertRedirect(route('categories.index'));

        $this->assertSame('Groceries', $category->refresh()->name);
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        $category = $family->categories()->firstWhere('name', 'Food');
        Expense::factory()->for($family)->create(['user_id' => $user->id, 'expense_category_id' => $category->id]);

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }

    public function test_an_unused_category_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        $category = $family->categories()->firstWhere('name', 'Others');

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
    }

    public function test_a_user_cannot_manage_another_familys_category(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);

        $otherOwner = User::factory()->create();
        $otherFamily = app(FamilyService::class)->createForUser($otherOwner);
        $foreign = $otherFamily->categories()->first();

        $this->actingAs($user)
            ->put(route('categories.update', $foreign), ['name' => 'Hacked'])
            ->assertForbidden();
    }
}
