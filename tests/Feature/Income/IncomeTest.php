<?php

namespace Tests\Feature\Income;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\Income;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\IncomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Family $family): array
    {
        return [
            'income_source_id' => $family->incomeSources()->first()->id,
            'income_date' => now()->format('Y-m-d'),
            'amount' => 50000,
            'notes' => 'Monthly salary',
        ];
    }

    public function test_guests_are_redirected_from_the_income_list(): void
    {
        $this->get(route('incomes.index'))->assertRedirect(route('login'));
    }

    public function test_a_family_member_can_view_the_income_list(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)->get(route('incomes.index'))->assertOk();
    }

    public function test_default_income_sources_are_seeded_for_a_new_family(): void
    {
        [, $family] = $this->userWithFamily();

        $this->assertCount(count(FamilyService::DEFAULT_INCOME_SOURCES), $family->incomeSources);
        $this->assertNotNull($family->incomeSources()->firstWhere('name', 'Salary'));
    }

    public function test_a_member_can_create_income(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('incomes.store'), $this->validPayload($family))
            ->assertRedirect(route('incomes.index'));

        $this->assertDatabaseHas('incomes', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'notes' => 'Monthly salary',
        ]);
    }

    public function test_creating_income_requires_valid_data(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('incomes.store'), ['amount' => 'nope'])
            ->assertSessionHasErrors(['income_source_id', 'income_date', 'amount']);
    }

    public function test_a_source_from_another_family_cannot_be_used(): void
    {
        [$user, $family] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();

        $payload = $this->validPayload($family);
        $payload['income_source_id'] = $otherFamily->incomeSources()->first()->id;

        $this->actingAs($user)
            ->post(route('incomes.store'), $payload)
            ->assertSessionHasErrors('income_source_id');
    }

    public function test_a_member_can_update_their_own_income(): void
    {
        [$user, $family] = $this->userWithFamily();
        $income = Income::factory()->for($family)->create(['user_id' => $user->id]);

        $payload = $this->validPayload($family);
        $payload['amount'] = 12345;

        $this->actingAs($user)
            ->put(route('incomes.update', $income), $payload)
            ->assertRedirect(route('incomes.index'));

        $this->assertSame('12345.00', $income->refresh()->amount);
    }

    public function test_a_member_cannot_update_another_members_income(): void
    {
        [$owner, $family] = $this->userWithFamily();
        $otherMember = $this->addMember($family);

        $income = Income::factory()->for($family)->create(['user_id' => $owner->id]);

        $this->actingAs($otherMember)
            ->put(route('incomes.update', $income), $this->validPayload($family))
            ->assertForbidden();
    }

    public function test_an_admin_can_update_any_family_members_income(): void
    {
        [$admin, $family] = $this->userWithFamily();
        $member = $this->addMember($family);

        $income = Income::factory()->for($family)->create(['user_id' => $member->id]);

        $payload = $this->validPayload($family);
        $payload['amount'] = 7777;

        $this->actingAs($admin)
            ->put(route('incomes.update', $income), $payload)
            ->assertRedirect(route('incomes.index'));

        $this->assertSame('7777.00', $income->refresh()->amount);
    }

    public function test_a_user_cannot_touch_another_familys_income(): void
    {
        [$user] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();

        $foreignIncome = Income::factory()->for($otherFamily)->create([
            'user_id' => $otherFamily->created_by,
        ]);

        $this->actingAs($user)
            ->get(route('incomes.edit', $foreignIncome))
            ->assertForbidden();
    }

    public function test_deleting_income_soft_deletes_it(): void
    {
        [$user, $family] = $this->userWithFamily();
        $income = Income::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('incomes.destroy', $income))
            ->assertRedirect(route('incomes.index'));

        $this->assertSoftDeleted($income);
    }

    public function test_the_list_can_be_filtered_by_source(): void
    {
        [$user, $family] = $this->userWithFamily();
        $sources = $family->incomeSources()->orderBy('id')->get();
        $wanted = $sources->first();
        $other = $sources->get(1);

        Income::factory()->for($family)->create([
            'user_id' => $user->id,
            'income_source_id' => $wanted->id,
            'notes' => 'Keep me',
        ]);
        Income::factory()->for($family)->create([
            'user_id' => $user->id,
            'income_source_id' => $other->id,
            'notes' => 'Filter me out',
        ]);

        $this->actingAs($user)
            ->get(route('incomes.index', ['income_source_id' => $wanted->id]))
            ->assertOk()
            ->assertSee('Keep me')
            ->assertDontSee('Filter me out');
    }

    public function test_the_monthly_report_totals_income_per_source(): void
    {
        [$user, $family] = $this->userWithFamily();
        $source = $family->incomeSources()->first();

        Income::factory()->for($family)->create([
            'user_id' => $user->id,
            'income_source_id' => $source->id,
            'income_date' => '2026-07-05',
            'amount' => 30000,
        ]);
        Income::factory()->for($family)->create([
            'user_id' => $user->id,
            'income_source_id' => $source->id,
            'income_date' => '2026-07-20',
            'amount' => 20000,
        ]);
        // A different month — must be excluded.
        Income::factory()->for($family)->create([
            'user_id' => $user->id,
            'income_source_id' => $source->id,
            'income_date' => '2026-06-10',
            'amount' => 99999,
        ]);

        $report = app(IncomeService::class)->monthlyReport($family, '2026-07');

        $this->assertSame('50000', (string) $report['total']);
        $this->assertCount(1, $report['rows']);
        $this->assertEquals(50000, $report['rows']->first()->total);
    }

    public function test_the_report_page_renders(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)
            ->get(route('incomes.report', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('July 2026');
    }
}
