<?php

namespace Tests\Feature\Dashboard;

use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze "now" so "this month" is deterministic regardless of run date.
        Carbon::setTestNow('2026-07-15');

        $this->service = app(DashboardService::class);
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

    public function test_summary_totals_income_and_expense_for_the_current_month(): void
    {
        [$user, $family] = $this->userWithFamily();
        $source = $family->incomeSources()->first();
        $category = $family->categories()->first();

        Income::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => $source->id,
            'income_date' => '2026-07-03', 'amount' => 50000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-10', 'amount' => 20000,
        ]);
        // Previous month — must be excluded from "this month".
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-06-30', 'amount' => 99999,
        ]);

        $summary = $this->service->summary($family);

        $this->assertSame('50000', $summary['income']);
        $this->assertSame('20000', $summary['expense']);
        $this->assertSame('30000', $summary['balance']);
    }

    public function test_balance_can_be_negative(): void
    {
        [$user, $family] = $this->userWithFamily();
        $category = $family->categories()->first();

        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-10', 'amount' => 5000,
        ]);

        $this->assertSame('-5000', $this->service->summary($family)['balance']);
    }

    public function test_spending_by_category_groups_and_orders_by_total(): void
    {
        [$user, $family] = $this->userWithFamily();
        $categories = $family->categories()->orderBy('id')->get();
        $food = $categories->first();
        $travel = $categories->get(1);

        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $food->id,
            'expense_date' => '2026-07-05', 'amount' => 300,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $food->id,
            'expense_date' => '2026-07-06', 'amount' => 200,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $travel->id,
            'expense_date' => '2026-07-07', 'amount' => 1000,
        ]);

        $result = $this->service->spendingByCategory($family);

        $this->assertCount(2, $result);
        // Largest first: travel (1000) before food (500).
        $this->assertSame($travel->name, $result[0]['name']);
        $this->assertSame(1000.0, $result[0]['total']);
        $this->assertSame(500.0, $result[1]['total']);
    }

    public function test_monthly_trend_returns_a_gap_free_series(): void
    {
        [$user, $family] = $this->userWithFamily();
        $source = $family->incomeSources()->first();
        $category = $family->categories()->first();

        Income::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => $source->id,
            'income_date' => '2026-07-02', 'amount' => 40000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-05-02', 'amount' => 1500,
        ]);

        $trend = $this->service->monthlyTrend($family, 6);

        // Six contiguous months, Feb..Jul 2026, even where there is no activity.
        $this->assertCount(6, $trend);
        $this->assertSame('Feb 2026', $trend->first()['label']);
        $this->assertSame('Jul 2026', $trend->last()['label']);

        $july = $trend->firstWhere('label', 'Jul 2026');
        $this->assertSame(40000.0, $july['income']);
        $this->assertSame(0.0, $july['expense']);

        $may = $trend->firstWhere('label', 'May 2026');
        $this->assertSame(1500.0, $may['expense']);
    }
}
