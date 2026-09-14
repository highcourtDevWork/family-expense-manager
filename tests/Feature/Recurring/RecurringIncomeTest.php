<?php

namespace Tests\Feature\Recurring;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\RecurringIncome;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\RecurringIncomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringIncomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: User, 1: Family}
     */
    private function userWithFamily(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        return [$user, $family];
    }

    private function addMember(Family $family): User
    {
        $user = User::factory()->create();
        $family->members()->create([
            'user_id' => $user->id,
            'role' => FamilyRole::Member,
            'relationship' => Relationship::Spouse,
        ]);

        return $user;
    }

    public function test_a_member_can_create_a_salary_template(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)->post(route('recurring-incomes.store'), [
            'name' => 'My Salary',
            'amount' => 90000,
            'user_id' => $user->id,
            'auto_post' => '1',
        ])->assertRedirect(route('recurring-incomes.index'));

        $this->assertDatabaseHas('recurring_incomes', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'name' => 'My Salary',
            'amount' => 90000,
            'auto_post' => true,
        ]);
    }

    public function test_both_spouse_salaries_post_and_attribute_to_the_right_earner(): void
    {
        [$me, $family] = $this->userWithFamily();
        $wife = $this->addMember($family);

        $mine = RecurringIncome::factory()->for($family)->create([
            'user_id' => $me->id, 'name' => 'My Salary', 'amount' => 90000,
        ]);
        $hers = RecurringIncome::factory()->for($family)->create([
            'user_id' => $wife->id, 'name' => "Wife's Salary", 'amount' => 70000,
        ]);

        $this->actingAs($me)->post(route('recurring-incomes.post', $mine))->assertRedirect();
        $this->actingAs($me)->post(route('recurring-incomes.post', $hers))->assertRedirect();

        // Each salary is attributed to its earner, not to whoever posted it.
        $this->assertDatabaseHas('incomes', [
            'family_id' => $family->id, 'user_id' => $me->id, 'amount' => 90000, 'notes' => 'My Salary',
            'reference' => 'recurring-income:'.$mine->id.':2026-07', 'income_date' => '2026-07-01',
        ]);
        $this->assertDatabaseHas('incomes', [
            'family_id' => $family->id, 'user_id' => $wife->id, 'amount' => 70000, 'notes' => "Wife's Salary",
            'reference' => 'recurring-income:'.$hers->id.':2026-07',
        ]);
    }

    public function test_posting_twice_in_a_month_does_not_duplicate(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringIncome::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('recurring-incomes.post', $template));
        $this->actingAs($user)->post(route('recurring-incomes.post', $template))
            ->assertSessionHas('status', 'recurring-income-already-posted');

        $this->assertDatabaseCount('incomes', 1);
    }

    public function test_the_same_template_posts_again_in_a_new_month(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringIncome::factory()->for($family)->create(['user_id' => $user->id]);
        $service = app(RecurringIncomeService::class);

        $this->assertNotNull($service->postForMonth($template, Carbon::parse('2026-07-01')));
        $this->assertNotNull($service->postForMonth($template, Carbon::parse('2026-08-01')));

        $this->assertDatabaseCount('incomes', 2);
    }

    public function test_a_template_without_a_source_falls_back_to_salary(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringIncome::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => null,
        ]);

        $income = app(RecurringIncomeService::class)->postForMonth($template, now());

        $salary = $family->incomeSources()->firstWhere('name', 'Salary');
        $this->assertSame($salary->id, $income->income_source_id);
    }

    public function test_the_scheduler_only_posts_active_auto_post_templates(): void
    {
        [$user, $family] = $this->userWithFamily();
        RecurringIncome::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => true, 'active' => true]);
        RecurringIncome::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => false, 'active' => true]);
        RecurringIncome::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => true, 'active' => false]);

        $created = app(RecurringIncomeService::class)->postAllDue(Carbon::parse('2026-07-01'));

        $this->assertSame(1, $created);
        $this->assertDatabaseCount('incomes', 1);
    }

    public function test_the_earner_must_belong_to_the_family(): void
    {
        [$user] = $this->userWithFamily();
        [$outsider] = $this->userWithFamily(); // a different family's user

        $this->actingAs($user)->post(route('recurring-incomes.store'), [
            'name' => 'Bogus',
            'amount' => 1000,
            'user_id' => $outsider->id,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_a_user_cannot_post_another_familys_template(): void
    {
        [$user] = $this->userWithFamily();
        [$otherUser, $otherFamily] = $this->userWithFamily();
        $foreign = RecurringIncome::factory()->for($otherFamily)->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)->post(route('recurring-incomes.post', $foreign))->assertForbidden();
    }
}
