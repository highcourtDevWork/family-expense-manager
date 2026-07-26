<?php

namespace Tests\Feature\Debt;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Debt;
use App\Models\Family;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
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
            'person_name' => 'Ravi',
            'debt_type' => DebtType::Borrowed->value,
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'notes' => 'Emergency',
        ];
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('debts.index'))->assertRedirect(route('login'));
    }

    public function test_a_member_can_create_a_debt_starting_fully_unpaid(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('debts.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('debts', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'amount' => 10000,
            'paid_amount' => 0,
            'balance_amount' => 10000,
            'status' => DebtStatus::Open->value,
        ]);
    }

    public function test_recording_payments_updates_balance_and_settles_when_cleared(): void
    {
        [$user, $family] = $this->userWithFamily();
        $debt = Debt::factory()->for($family)->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'balance_amount' => 10000,
            'paid_amount' => 0,
            'status' => DebtStatus::Open,
        ]);

        // Partial payment.
        $this->actingAs($user)->post(route('debts.payments.store', $debt), [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 4000,
        ])->assertRedirect(route('debts.show', $debt));

        $debt->refresh();
        $this->assertSame('4000.00', $debt->paid_amount);
        $this->assertSame('6000.00', $debt->balance_amount);
        $this->assertSame(DebtStatus::Open, $debt->status);

        // Clear the rest.
        $this->actingAs($user)->post(route('debts.payments.store', $debt), [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 6000,
        ]);

        $debt->refresh();
        $this->assertSame('0.00', $debt->balance_amount);
        $this->assertSame(DebtStatus::Settled, $debt->status);
    }

    public function test_a_payment_cannot_exceed_the_outstanding_balance(): void
    {
        [$user, $family] = $this->userWithFamily();
        $debt = Debt::factory()->for($family)->create([
            'user_id' => $user->id, 'amount' => 5000, 'balance_amount' => 5000,
        ]);

        $this->actingAs($user)->post(route('debts.payments.store', $debt), [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 6000,
        ])->assertSessionHasErrors('amount');
    }

    public function test_deleting_a_payment_recalculates_the_balance(): void
    {
        [$user, $family] = $this->userWithFamily();
        $debt = Debt::factory()->for($family)->create([
            'user_id' => $user->id, 'amount' => 5000, 'balance_amount' => 5000,
        ]);

        $this->actingAs($user)->post(route('debts.payments.store', $debt), [
            'payment_date' => now()->format('Y-m-d'), 'amount' => 5000,
        ]);
        $this->assertSame(DebtStatus::Settled, $debt->refresh()->status);

        $payment = $debt->payments()->first();
        $this->actingAs($user)->delete(route('debts.payments.destroy', [$debt, $payment]))->assertRedirect();

        $debt->refresh();
        $this->assertSame('5000.00', $debt->balance_amount);
        $this->assertSame(DebtStatus::Open, $debt->status);
    }

    public function test_a_member_cannot_edit_another_members_debt(): void
    {
        [$owner, $family] = $this->userWithFamily();
        $other = $this->addMember($family);
        $debt = Debt::factory()->for($family)->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->put(route('debts.update', $debt), $this->validPayload())
            ->assertForbidden();
    }

    public function test_an_admin_can_edit_any_family_debt(): void
    {
        [$admin, $family] = $this->userWithFamily();
        $member = $this->addMember($family);
        $debt = Debt::factory()->for($family)->create(['user_id' => $member->id]);

        $payload = $this->validPayload();
        $payload['amount'] = 22222;

        $this->actingAs($admin)->put(route('debts.update', $debt), $payload)->assertRedirect();
        $this->assertSame('22222.00', $debt->refresh()->amount);
    }

    public function test_a_user_cannot_view_another_familys_debt(): void
    {
        [$user] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();
        $foreign = Debt::factory()->for($otherFamily)->create(['user_id' => $otherFamily->created_by]);

        $this->actingAs($user)->get(route('debts.show', $foreign))->assertForbidden();
    }

    public function test_deleting_a_debt_soft_deletes_it(): void
    {
        [$user, $family] = $this->userWithFamily();
        $debt = Debt::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('debts.destroy', $debt))->assertRedirect(route('debts.index'));
        $this->assertSoftDeleted($debt);
    }
}
