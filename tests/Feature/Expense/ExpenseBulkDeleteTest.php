<?php

namespace Tests\Feature\Expense;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Expense;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Family}
     */
    private function userWithFamily(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        return [$user, $family];
    }

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

    public function test_a_member_can_bulk_delete_their_own_expenses(): void
    {
        [$user, $family] = $this->userWithFamily();
        $expenses = Expense::factory()->count(3)->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('expenses.bulk-delete'), ['ids' => $expenses->pluck('id')->all()])
            ->assertRedirect(route('expenses.index'));

        foreach ($expenses as $expense) {
            $this->assertSoftDeleted($expense);
        }
    }

    public function test_bulk_delete_requires_at_least_one_id(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)
            ->delete(route('expenses.bulk-delete'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_a_member_cannot_bulk_delete_another_members_expenses(): void
    {
        [$owner, $family] = $this->userWithFamily();
        $other = $this->addMember($family);

        $mine = Expense::factory()->for($family)->create(['user_id' => $other->id]);
        $theirs = Expense::factory()->for($family)->create(['user_id' => $owner->id]);

        // "other" tries to delete both — only their own row goes.
        $this->actingAs($other)
            ->delete(route('expenses.bulk-delete'), ['ids' => [$mine->id, $theirs->id]])
            ->assertRedirect();

        $this->assertSoftDeleted($mine);
        $this->assertNotSoftDeleted($theirs);
    }

    public function test_an_admin_can_bulk_delete_any_family_members_expenses(): void
    {
        [$admin, $family] = $this->userWithFamily();
        $member = $this->addMember($family);
        $expenses = Expense::factory()->count(2)->for($family)->create(['user_id' => $member->id]);

        $this->actingAs($admin)
            ->delete(route('expenses.bulk-delete'), ['ids' => $expenses->pluck('id')->all()])
            ->assertRedirect();

        foreach ($expenses as $expense) {
            $this->assertSoftDeleted($expense);
        }
    }

    public function test_bulk_delete_cannot_reach_another_familys_expenses(): void
    {
        [$user] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();
        $foreign = Expense::factory()->for($otherFamily)->create(['user_id' => $otherFamily->created_by]);

        $this->actingAs($user)
            ->delete(route('expenses.bulk-delete'), ['ids' => [$foreign->id]])
            ->assertRedirect();

        $this->assertNotSoftDeleted($foreign);
    }
}
