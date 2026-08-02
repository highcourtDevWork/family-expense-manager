<?php

namespace Tests\Feature\Recurring;

use App\Models\Family;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\RecurringExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
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

    public function test_a_member_can_create_a_template(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)->post(route('recurring.store'), [
            'name' => 'House Rent',
            'amount' => 15000,
            'payment_method' => 'cash',
            'auto_post' => '1',
        ])->assertRedirect(route('recurring.index'));

        $this->assertDatabaseHas('recurring_expenses', [
            'family_id' => $family->id,
            'name' => 'House Rent',
            'amount' => 15000,
            'auto_post' => true,
        ]);
    }

    public function test_one_click_posts_the_expense_for_this_month(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringExpense::factory()->for($family)->create([
            'user_id' => $user->id, 'name' => 'Internet Bill', 'amount' => 1200,
        ]);

        $this->actingAs($user)->post(route('recurring.post', $template))->assertRedirect(route('recurring.index'));

        $this->assertDatabaseHas('expenses', [
            'family_id' => $family->id,
            'description' => 'Internet Bill',
            'amount' => 1200,
            'reference' => 'recurring:'.$template->id.':2026-07',
            'expense_date' => '2026-07-01 00:00:00',
        ]);
    }

    public function test_posting_twice_in_a_month_does_not_duplicate(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringExpense::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('recurring.post', $template));
        $this->actingAs($user)->post(route('recurring.post', $template))
            ->assertSessionHas('status', 'recurring-already-posted');

        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_the_same_template_posts_again_in_a_new_month(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringExpense::factory()->for($family)->create(['user_id' => $user->id]);
        $service = app(RecurringExpenseService::class);

        $this->assertNotNull($service->postForMonth($template, Carbon::parse('2026-07-01')));
        $this->assertNotNull($service->postForMonth($template, Carbon::parse('2026-08-01')));

        $this->assertDatabaseCount('expenses', 2);
    }

    public function test_the_scheduler_only_posts_active_auto_post_templates(): void
    {
        [$user, $family] = $this->userWithFamily();
        RecurringExpense::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => true, 'active' => true]);
        RecurringExpense::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => false, 'active' => true]);
        RecurringExpense::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => true, 'active' => false]);

        $created = app(RecurringExpenseService::class)->postAllDue(Carbon::parse('2026-07-01'));

        $this->assertSame(1, $created);
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_a_template_without_a_category_falls_back_to_others(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringExpense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => null,
        ]);

        $expense = app(RecurringExpenseService::class)->postForMonth($template, now());

        $others = $family->categories()->firstWhere('name', 'Others');
        $this->assertSame($others->id, $expense->expense_category_id);
    }

    public function test_a_user_cannot_post_another_familys_template(): void
    {
        [$user] = $this->userWithFamily();
        [$otherUser, $otherFamily] = $this->userWithFamily();
        $foreign = RecurringExpense::factory()->for($otherFamily)->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)->post(route('recurring.post', $foreign))->assertForbidden();
    }
}
