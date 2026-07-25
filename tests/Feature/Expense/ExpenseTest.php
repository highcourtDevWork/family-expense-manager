<?php

namespace Tests\Feature\Expense;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Expense;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user who is the admin of a freshly seeded family.
     *
     * @return array{0: User, 1: Family}
     */
    private function userWithFamily(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        return [$user, $family];
    }

    /**
     * Add an extra user to an existing family with the given role.
     */
    private function addMember(Family $family, FamilyRole $role = FamilyRole::Member): User
    {
        $user = User::factory()->create();

        $family->members()->create([
            'user_id' => $user->id,
            'role' => $role,
            'relationship' => Relationship::Son,
        ]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Family $family): array
    {
        return [
            'expense_category_id' => $family->categories()->first()->id,
            'expense_date' => now()->format('Y-m-d'),
            'amount' => 500,
            'payment_method' => 'cash',
            'description' => 'Lunch',
        ];
    }

    public function test_guests_are_redirected_from_the_expense_list(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
    }

    public function test_a_family_member_can_view_the_expense_list(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)->get(route('expenses.index'))->assertOk();
    }

    public function test_a_member_can_create_an_expense(): void
    {
        [$user, $family] = $this->userWithFamily();

        $response = $this->actingAs($user)
            ->post(route('expenses.store'), $this->validPayload($family));

        $response->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'amount' => 500,
            'description' => 'Lunch',
        ]);
    }

    public function test_creating_an_expense_requires_valid_data(): void
    {
        [$user] = $this->userWithFamily();

        $response = $this->actingAs($user)
            ->post(route('expenses.store'), [
                'amount' => 'not-a-number',
            ]);

        $response->assertSessionHasErrors(['expense_category_id', 'expense_date', 'amount', 'payment_method']);
    }

    public function test_a_category_from_another_family_cannot_be_used(): void
    {
        [$user, $family] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();

        $payload = $this->validPayload($family);
        $payload['expense_category_id'] = $otherFamily->categories()->first()->id;

        $this->actingAs($user)
            ->post(route('expenses.store'), $payload)
            ->assertSessionHasErrors('expense_category_id');
    }

    public function test_an_uploaded_receipt_is_stored(): void
    {
        Storage::fake('public');

        [$user, $family] = $this->userWithFamily();

        $payload = $this->validPayload($family);
        $payload['attachment'] = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('expenses.store'), $payload);

        $expense = Expense::firstOrFail();

        $this->assertNotNull($expense->attachment_path);
        Storage::disk('public')->assertExists($expense->attachment_path);
    }

    public function test_a_member_can_update_their_own_expense(): void
    {
        [$user, $family] = $this->userWithFamily();
        $expense = Expense::factory()->for($family)->create(['user_id' => $user->id]);

        $payload = $this->validPayload($family);
        $payload['amount'] = 999;

        $this->actingAs($user)
            ->put(route('expenses.update', $expense), $payload)
            ->assertRedirect(route('expenses.index'));

        $this->assertSame('999.00', $expense->refresh()->amount);
    }

    public function test_a_member_cannot_update_another_members_expense(): void
    {
        [$owner, $family] = $this->userWithFamily();
        $otherMember = $this->addMember($family);

        $expense = Expense::factory()->for($family)->create(['user_id' => $owner->id]);

        $this->actingAs($otherMember)
            ->put(route('expenses.update', $expense), $this->validPayload($family))
            ->assertForbidden();
    }

    public function test_an_admin_can_update_any_family_members_expense(): void
    {
        [$admin, $family] = $this->userWithFamily();
        $member = $this->addMember($family);

        $expense = Expense::factory()->for($family)->create(['user_id' => $member->id]);

        $payload = $this->validPayload($family);
        $payload['amount'] = 1234;

        $this->actingAs($admin)
            ->put(route('expenses.update', $expense), $payload)
            ->assertRedirect(route('expenses.index'));

        $this->assertSame('1234.00', $expense->refresh()->amount);
    }

    public function test_a_user_cannot_touch_another_familys_expense(): void
    {
        [$user] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();

        $foreignExpense = Expense::factory()->for($otherFamily)->create([
            'user_id' => $otherFamily->created_by,
        ]);

        $this->actingAs($user)
            ->get(route('expenses.edit', $foreignExpense))
            ->assertForbidden();
    }

    public function test_deleting_an_expense_soft_deletes_it(): void
    {
        [$user, $family] = $this->userWithFamily();
        $expense = Expense::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertSoftDeleted($expense);
    }

    public function test_the_list_can_be_filtered_by_category(): void
    {
        [$user, $family] = $this->userWithFamily();
        $categories = $family->categories()->orderBy('id')->get();
        $wanted = $categories->first();
        $other = $categories->get(1);

        Expense::factory()->for($family)->create([
            'user_id' => $user->id,
            'expense_category_id' => $wanted->id,
            'description' => 'Keep me',
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id,
            'expense_category_id' => $other->id,
            'description' => 'Filter me out',
        ]);

        $this->actingAs($user)
            ->get(route('expenses.index', ['category_id' => $wanted->id]))
            ->assertOk()
            ->assertSee('Keep me')
            ->assertDontSee('Filter me out');
    }

    public function test_the_list_can_be_searched_by_description(): void
    {
        [$user, $family] = $this->userWithFamily();

        Expense::factory()->for($family)->create([
            'user_id' => $user->id,
            'description' => 'Groceries at market',
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id,
            'description' => 'Movie tickets',
        ]);

        $this->actingAs($user)
            ->get(route('expenses.index', ['search' => 'Groceries']))
            ->assertOk()
            ->assertSee('Groceries at market')
            ->assertDontSee('Movie tickets');
    }
}
