<?php

namespace Tests\Feature\SavingsRate;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\IncomeAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SavingsRateTest extends TestCase
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

    private function seedJulyFinances(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        Income::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => $family->incomeSources()->first()->id,
            'income_date' => '2026-07-03', 'amount' => 90000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $family->categories()->first()->id,
            'expense_date' => '2026-07-10', 'amount' => 45000,
        ]);
        Saving::factory()->for($family)->create([
            'user_id' => $user->id, 'date' => '2026-07-09', 'amount' => 15000,
        ]);
        $loan = Loan::factory()->for($family)->create(['user_id' => $user->id]);
        $loan->payments()->create(['payment_date' => '2026-07-05', 'amount' => 12000]);

        return [$user, $family];
    }

    public function test_income_is_allocated_across_the_four_buckets(): void
    {
        [, $family] = $this->seedJulyFinances();

        $data = app(IncomeAllocationService::class)->forPeriod(
            $family, Carbon::parse('2026-07-01')->startOfMonth(), Carbon::parse('2026-07-01')->endOfMonth()
        );

        $this->assertEqualsWithDelta(90000, $data['income'], 0.01);
        $this->assertEqualsWithDelta(45000, $data['expenses'], 0.01);
        $this->assertEqualsWithDelta(12000, $data['loans'], 0.01);
        $this->assertEqualsWithDelta(15000, $data['savingsDeposits'], 0.01);
        // 90000 - 45000 - 12000 - 15000
        $this->assertEqualsWithDelta(18000, $data['leftover'], 0.01);
        // deposits (15000) + leftover (18000)
        $this->assertEqualsWithDelta(33000, $data['saved'], 0.01);

        $this->assertEqualsWithDelta(36.7, $data['savingsRate'], 0.05);
        $this->assertEqualsWithDelta(50.0, $data['expenseRate'], 0.05);
        $this->assertEqualsWithDelta(13.3, $data['loanRate'], 0.05);
        $this->assertEqualsWithDelta(16.7, $data['depositRate'], 0.05);
        $this->assertEqualsWithDelta(20.0, $data['leftoverRate'], 0.05);
    }

    public function test_a_loan_payment_outside_the_period_is_excluded(): void
    {
        [$user, $family] = $this->seedJulyFinances();
        // An August payment must not count toward July.
        $family->loans()->first()->payments()->create(['payment_date' => '2026-08-02', 'amount' => 5000]);

        $data = app(IncomeAllocationService::class)->forPeriod(
            $family, Carbon::parse('2026-07-01')->startOfMonth(), Carbon::parse('2026-07-01')->endOfMonth()
        );

        $this->assertEqualsWithDelta(12000, $data['loans'], 0.01);
    }

    public function test_the_page_renders_with_the_savings_rate(): void
    {
        [$user] = $this->seedJulyFinances();

        $this->actingAs($user)
            ->get(route('savings-rate.index'))
            ->assertOk()
            ->assertSee('Savings rate')
            ->assertSee('36.7%');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('savings-rate.index'))->assertRedirect(route('login'));
    }

    public function test_loan_payments_show_even_in_a_month_with_no_income(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        // A manual (non-EMI) loan payment in July, with no income that month.
        $loan = Loan::factory()->for($family)->create(['user_id' => $user->id]);
        $loan->payments()->create(['payment_date' => '2026-07-05', 'amount' => 40000]);

        // Default period is "this month" = July 2026 (frozen in setUp).
        $this->actingAs($user)
            ->get(route('savings-rate.index'))
            ->assertOk()
            ->assertDontSee('No income or spending recorded')
            ->assertSee('Loan / EMI payments')
            ->assertSee('40,000.00');
    }
}
