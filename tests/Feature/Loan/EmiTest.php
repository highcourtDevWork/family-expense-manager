<?php

namespace Tests\Feature\Loan;

use App\Enums\EmiStatus;
use App\Enums\LoanStatus;
use App\Models\Family;
use App\Models\Loan;
use App\Models\User;
use App\Services\EmiService;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmiTest extends TestCase
{
    use RefreshDatabase;

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

    private function emiLoan(array $overrides = []): Loan
    {
        [$user, $family] = $this->userWithFamily();

        return Loan::factory()->for($family)->create(array_merge([
            'user_id' => $user->id,
            'is_emi' => true,
            'total_amount' => 100000,
            'tenure_months' => 12,
            'interest_rate' => 12,
            'emi_due_day' => 5,
            'emi_amount' => null,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
            'status' => LoanStatus::Active,
        ], $overrides));
    }

    public function test_emi_is_calculated_with_the_standard_formula(): void
    {
        // ₹1,00,000 @ 12% p.a. over 12 months → ≈ ₹8,884.88.
        $emi = app(EmiService::class)->calculateEmi(100000, 12, 12);

        $this->assertEqualsWithDelta(8884.88, $emi, 0.01);
    }

    public function test_zero_interest_emi_is_principal_over_months(): void
    {
        $this->assertSame(5000.0, app(EmiService::class)->calculateEmi(60000, 0, 12));
    }

    public function test_generating_a_schedule_creates_one_row_per_month_that_amortises_to_zero(): void
    {
        $loan = $this->emiLoan();

        app(EmiService::class)->generateSchedule($loan);

        $emis = $loan->emis()->get();
        $this->assertCount(12, $emis);

        // Principal components repay exactly the borrowed amount.
        $this->assertEqualsWithDelta(100000, $emis->sum('principal_component'), 0.05);

        // First installment: interest on the full balance, principal is the rest.
        $first = $emis->first();
        $this->assertEqualsWithDelta(1000.0, (float) $first->interest_component, 0.01); // 100000 * 1%
        $this->assertSame('0.00', $emis->last()->closing_balance); // fully cleared

        // Due dates follow the configured day, one month apart.
        $this->assertSame('2026-02-05', $first->due_date->format('Y-m-d'));
    }

    public function test_creating_an_emi_loan_via_http_builds_the_schedule(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)->post(route('loans.store'), [
            'loan_name' => 'Car EMI',
            'loan_type' => 'car',
            'total_amount' => 100000,
            'start_date' => '2026-01-01',
            'is_emi' => '1',
            'tenure_months' => 12,
            'interest_rate' => 12,
            'emi_due_day' => 5,
        ])->assertRedirect();

        $loan = Loan::firstWhere('loan_name', 'Car EMI');
        $this->assertNotNull($loan);
        $this->assertTrue($loan->is_emi);
        $this->assertCount(12, $loan->emis()->get());
    }

    public function test_due_installments_post_automatically_and_update_the_balance(): void
    {
        $loan = $this->emiLoan();
        app(EmiService::class)->generateSchedule($loan);

        // As of 10 Apr 2026, the Feb/Mar/Apr (5th) installments are due.
        Carbon::setTestNow('2026-04-10');
        $posted = app(EmiService::class)->postDueInstallments();

        $this->assertSame(3, $posted);
        $this->assertSame(3, $loan->emis()->where('status', EmiStatus::Paid)->count());

        $paidPrincipal = (float) $loan->emis()->where('status', EmiStatus::Paid)->sum('principal_component');
        $loan->refresh();
        $this->assertEqualsWithDelta($paidPrincipal, (float) $loan->paid_amount, 0.01);
        $this->assertEqualsWithDelta(100000 - $paidPrincipal, (float) $loan->remaining_amount, 0.01);
        $this->assertSame(LoanStatus::Active, $loan->status);
    }

    public function test_a_loan_closes_once_every_installment_is_posted(): void
    {
        $loan = $this->emiLoan();
        app(EmiService::class)->generateSchedule($loan);

        // Far in the future — all 12 installments are due.
        Carbon::setTestNow('2027-06-01');
        app(EmiService::class)->postDueInstallments();

        $loan->refresh();
        $this->assertSame(0, $loan->emis()->where('status', EmiStatus::Pending)->count());
        $this->assertSame('0.00', $loan->remaining_amount);
        $this->assertSame(LoanStatus::Closed, $loan->status);
    }

    public function test_the_schedule_is_not_regenerated_after_repayment_starts(): void
    {
        $loan = $this->emiLoan();
        app(EmiService::class)->generateSchedule($loan);

        Carbon::setTestNow('2026-02-10');
        app(EmiService::class)->postDueInstallments(); // pays installment #1

        // Attempting to regenerate (e.g. via an edit) must not wipe paid history.
        app(EmiService::class)->generateSchedule($loan->refresh());

        $this->assertSame(1, $loan->emis()->where('status', EmiStatus::Paid)->count());
        $this->assertCount(12, $loan->emis()->get());
    }

    public function test_a_non_emi_loan_has_no_schedule(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create(['user_id' => $user->id, 'is_emi' => false]);

        app(EmiService::class)->generateSchedule($loan);

        $this->assertSame(0, $loan->emis()->count());
    }
}
