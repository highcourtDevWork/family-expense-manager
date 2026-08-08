<?php

namespace Tests\Feature\Loan;

use App\Enums\EmiStatus;
use App\Enums\FamilyRole;
use App\Enums\LoanStatus;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\Loan;
use App\Models\User;
use App\Services\EmiService;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmiManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Family, 2: Loan}
     */
    private function emiLoan(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id,
            'is_emi' => true,
            'total_amount' => 120000,
            'tenure_months' => 12,
            'interest_rate' => 12,
            'emi_due_day' => 5,
            'emi_amount' => null,
            'start_date' => '2026-01-01',
            'paid_amount' => 0,
            'remaining_amount' => 120000,
            'status' => LoanStatus::Active,
        ]);

        app(EmiService::class)->generateSchedule($loan);

        return [$user, $family, $loan];
    }

    public function test_a_pending_installment_can_be_marked_paid(): void
    {
        [$user, , $loan] = $this->emiLoan();
        $emi = $loan->emis()->first();

        $this->actingAs($user)
            ->post(route('loans.emis.pay', [$loan, $emi]))
            ->assertRedirect();

        $emi->refresh();
        $this->assertSame(EmiStatus::Paid, $emi->status);
        $this->assertNotNull($emi->paid_on);

        // The loan's outstanding principal drops by that installment's principal.
        $loan->refresh();
        $this->assertEqualsWithDelta(
            120000 - (float) $emi->principal_component,
            (float) $loan->remaining_amount,
            0.01,
        );
    }

    public function test_a_paid_installment_can_be_reverted_to_pending(): void
    {
        [$user, , $loan] = $this->emiLoan();
        $emi = $loan->emis()->first();

        $this->actingAs($user)->post(route('loans.emis.pay', [$loan, $emi]));
        $this->actingAs($user)->post(route('loans.emis.unpay', [$loan, $emi]))->assertRedirect();

        $emi->refresh();
        $this->assertSame(EmiStatus::Pending, $emi->status);
        $this->assertNull($emi->paid_on);

        $loan->refresh();
        $this->assertSame('120000.00', $loan->remaining_amount);
    }

    public function test_marking_the_last_pending_installment_closes_the_loan(): void
    {
        [$user, , $loan] = $this->emiLoan();

        foreach ($loan->emis()->get() as $emi) {
            $this->actingAs($user)->post(route('loans.emis.pay', [$loan, $emi]));
        }

        $loan->refresh();
        $this->assertSame(LoanStatus::Closed, $loan->status);
        $this->assertSame('0.00', $loan->remaining_amount);
    }

    public function test_a_plain_member_cannot_mark_another_members_loan_installment(): void
    {
        [, $family, $loan] = $this->emiLoan();

        $intruder = User::factory()->create();
        $family->members()->create([
            'user_id' => $intruder->id,
            'role' => FamilyRole::Member,
            'relationship' => Relationship::Son,
        ]);

        $this->actingAs($intruder)
            ->post(route('loans.emis.pay', [$loan, $loan->emis()->first()]))
            ->assertForbidden();
    }

    public function test_an_installment_from_another_loan_is_rejected(): void
    {
        [$user, , $loan] = $this->emiLoan();
        [, , $otherLoan] = $this->emiLoan();
        $foreignEmi = $otherLoan->emis()->first();

        // $user owns $loan but not $otherLoan; mismatched pair must 404/403.
        $this->actingAs($user)
            ->post(route('loans.emis.pay', [$loan, $foreignEmi]))
            ->assertNotFound();
    }
}
