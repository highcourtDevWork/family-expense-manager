<?php

namespace Tests\Feature\Api;

use App\Models\ApiCredential;
use App\Models\Expense;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiExpenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Family, 2: array<string, string>}
     */
    private function apiUser(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        [$credential, $secret] = ApiCredential::generate($user, 'Test');

        return [$user, $family, [
            'X-Api-Key' => $credential->key,
            'X-Api-Secret' => $secret,
            'Accept' => 'application/json',
        ]];
    }

    public function test_it_lists_family_expenses(): void
    {
        [$user, $family, $headers] = $this->apiUser();
        Expense::factory()->count(3)->for($family)->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/expenses', $headers)
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_creates_an_expense(): void
    {
        [$user, $family, $headers] = $this->apiUser();
        $category = $family->categories()->first();

        $response = $this->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id,
            'expense_date' => '2026-07-10',
            'amount' => 750,
            'payment_method' => 'upi',
            'description' => 'Lunch via API',
        ], $headers);

        $response->assertCreated()
            ->assertJsonPath('data.amount', 750)
            ->assertJsonPath('data.description', 'Lunch via API');

        $this->assertDatabaseHas('expenses', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'description' => 'Lunch via API',
        ]);
    }

    public function test_it_validates_input(): void
    {
        [, , $headers] = $this->apiUser();

        $this->postJson('/api/v1/expenses', ['amount' => 'nope'], $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expense_category_id', 'expense_date', 'amount', 'payment_method']);
    }

    public function test_a_category_from_another_family_is_rejected(): void
    {
        [, , $headers] = $this->apiUser();
        $otherUser = User::factory()->create();
        $otherFamily = app(FamilyService::class)->createForUser($otherUser);

        $this->postJson('/api/v1/expenses', [
            'expense_category_id' => $otherFamily->categories()->first()->id,
            'expense_date' => '2026-07-10',
            'amount' => 100,
            'payment_method' => 'cash',
        ], $headers)->assertStatus(422)->assertJsonValidationErrors('expense_category_id');
    }

    public function test_it_updates_and_deletes_an_expense(): void
    {
        [$user, $family, $headers] = $this->apiUser();
        $expense = Expense::factory()->for($family)->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/expenses/{$expense->id}", [
            'expense_category_id' => $family->categories()->first()->id,
            'expense_date' => '2026-07-11',
            'amount' => 999,
            'payment_method' => 'card',
        ], $headers)->assertOk()->assertJsonPath('data.amount', 999);

        $this->deleteJson("/api/v1/expenses/{$expense->id}", [], $headers)->assertOk();
        $this->assertSoftDeleted($expense);
    }

    public function test_it_cannot_touch_another_familys_expense(): void
    {
        [, , $headers] = $this->apiUser();
        $otherUser = User::factory()->create();
        $otherFamily = app(FamilyService::class)->createForUser($otherUser);
        $foreign = Expense::factory()->for($otherFamily)->create(['user_id' => $otherUser->id]);

        $this->getJson("/api/v1/expenses/{$foreign->id}", $headers)->assertForbidden();
    }

    public function test_the_dashboard_endpoint_returns_totals(): void
    {
        [, , $headers] = $this->apiUser();

        $this->getJson('/api/v1/dashboard', $headers)
            ->assertOk()
            ->assertJsonStructure(['data' => ['month', 'income', 'expense', 'balance', 'loan_balance', 'net_debt', 'savings']]);
    }
}
