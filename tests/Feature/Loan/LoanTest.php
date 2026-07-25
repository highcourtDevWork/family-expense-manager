<?php

namespace Tests\Feature\Loan;

use App\Enums\FamilyRole;
use App\Enums\LoanStatus;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\Loan;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
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
    private function validPayload(): array
    {
        return [
            'loan_name' => 'Home Loan',
            'loan_type' => 'home',
            'total_amount' => 2000000,
            'start_date' => '2026-01-01',
            'end_date' => '2031-01-01',
        ];
    }

    public function test_guests_are_redirected_from_loans(): void
    {
        $this->get(route('loans.index'))->assertRedirect(route('login'));
    }

    public function test_a_member_can_create_a_loan_that_starts_fully_unpaid(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('loans.store'), $this->validPayload())
            ->assertRedirect();

        $loan = Loan::firstOrFail();

        $this->assertSame($family->id, $loan->family_id);
        $this->assertSame('0.00', $loan->paid_amount);
        $this->assertSame('2000000.00', $loan->remaining_amount);
        $this->assertSame(LoanStatus::Active, $loan->status);
    }

    public function test_creating_a_loan_requires_valid_data(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('loans.store'), ['total_amount' => -5])
            ->assertSessionHasErrors(['loan_name', 'loan_type', 'total_amount', 'start_date']);
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        [$user] = $this->userWithFamily();

        $payload = $this->validPayload();
        $payload['end_date'] = '2025-01-01';

        $this->actingAs($user)
            ->post(route('loans.store'), $payload)
            ->assertSessionHasErrors('end_date');
    }

    public function test_recording_a_payment_recalculates_paid_and_remaining(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
        ]);

        $this->actingAs($user)
            ->post(route('loans.payments.store', $loan), [
                'payment_date' => '2026-02-01',
                'amount' => 30000,
            ])
            ->assertRedirect(route('loans.show', $loan));

        $loan->refresh();
        $this->assertSame('30000.00', $loan->paid_amount);
        $this->assertSame('70000.00', $loan->remaining_amount);
        $this->assertSame(LoanStatus::Active, $loan->status);
    }

    public function test_full_repayment_closes_the_loan(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id,
            'total_amount' => 50000,
            'paid_amount' => 0,
            'remaining_amount' => 50000,
        ]);

        $this->actingAs($user)->post(route('loans.payments.store', $loan), [
            'payment_date' => '2026-02-01',
            'amount' => 50000,
        ]);

        $loan->refresh();
        $this->assertSame('0.00', $loan->remaining_amount);
        $this->assertSame(LoanStatus::Closed, $loan->status);
    }

    public function test_a_payment_cannot_exceed_the_remaining_balance(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
        ]);

        $this->actingAs($user)
            ->post(route('loans.payments.store', $loan), [
                'payment_date' => '2026-02-01',
                'amount' => 15000,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('0.00', $loan->refresh()->paid_amount);
    }

    public function test_deleting_a_payment_reopens_and_recalculates_the_loan(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id,
            'total_amount' => 50000,
            'paid_amount' => 0,
            'remaining_amount' => 50000,
        ]);

        $this->actingAs($user)->post(route('loans.payments.store', $loan), [
            'payment_date' => '2026-02-01',
            'amount' => 50000,
        ]);
        $loan->refresh();
        $this->assertSame(LoanStatus::Closed, $loan->status);

        $payment = $loan->payments()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('loans.payments.destroy', [$loan, $payment]))
            ->assertRedirect(route('loans.show', $loan));

        $loan->refresh();
        $this->assertSame('0.00', $loan->paid_amount);
        $this->assertSame('50000.00', $loan->remaining_amount);
        $this->assertSame(LoanStatus::Active, $loan->status);
    }

    public function test_updating_the_total_recalculates_the_remaining_balance(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_amount' => 100000,
        ]);
        $loan->payments()->create(['payment_date' => '2026-02-01', 'amount' => 40000]);
        app(LoanService::class)->recalculate($loan);

        $this->actingAs($user)->put(route('loans.update', $loan), [
            'loan_name' => $loan->loan_name,
            'loan_type' => $loan->loan_type->value,
            'total_amount' => 60000,
            'start_date' => $loan->start_date->format('Y-m-d'),
        ]);

        $loan->refresh();
        $this->assertSame('40000.00', $loan->paid_amount);
        $this->assertSame('20000.00', $loan->remaining_amount);
    }

    public function test_a_member_cannot_manage_another_members_loan(): void
    {
        [$owner, $family] = $this->userWithFamily();
        $otherMember = $this->addMember($family);
        $loan = Loan::factory()->for($family)->create(['user_id' => $owner->id]);

        $this->actingAs($otherMember)
            ->post(route('loans.payments.store', $loan), [
                'payment_date' => '2026-02-01',
                'amount' => 100,
            ])
            ->assertForbidden();
    }

    public function test_an_admin_can_manage_any_family_loan(): void
    {
        [$admin, $family] = $this->userWithFamily();
        $member = $this->addMember($family);
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $member->id,
            'total_amount' => 10000,
            'remaining_amount' => 10000,
        ]);

        $this->actingAs($admin)
            ->post(route('loans.payments.store', $loan), [
                'payment_date' => '2026-02-01',
                'amount' => 1000,
            ])
            ->assertRedirect(route('loans.show', $loan));

        $this->assertSame('1000.00', $loan->refresh()->paid_amount);
    }

    public function test_a_user_cannot_view_another_familys_loan(): void
    {
        [$user] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();
        $foreignLoan = Loan::factory()->for($otherFamily)->create([
            'user_id' => $otherFamily->created_by,
        ]);

        $this->actingAs($user)
            ->get(route('loans.show', $foreignLoan))
            ->assertForbidden();
    }

    public function test_deleting_a_loan_soft_deletes_it(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('loans.destroy', $loan))
            ->assertRedirect(route('loans.index'));

        $this->assertSoftDeleted($loan);
    }
}
