<?php

namespace Tests\Feature\Bachelor;

use App\Enums\AccountType;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\SplitShare;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\SplitExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BachelorSplitTest extends TestCase
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
     * A bachelor account with $extra additional members beyond the owner.
     *
     * @return array{0: User, 1: Family, 2: Collection<int, User>}
     */
    private function bachelorAccount(int $extra = 3): array
    {
        $owner = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($owner, null, AccountType::Bachelor);

        $members = collect([$owner]);
        for ($i = 0; $i < $extra; $i++) {
            $member = User::factory()->create();
            $family->members()->create(['user_id' => $member->id, 'role' => FamilyRole::Member]);
            $members->push($member);
        }

        return [$owner, $family, $members];
    }

    public function test_signup_can_create_a_bachelor_account(): void
    {
        $this->post(route('register'), [
            'name' => 'Ravi',
            'email' => 'ravi@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'account_type' => 'bachelor',
        ])->assertRedirect(route('dashboard'));

        $family = User::firstWhere('email', 'ravi@example.com')->currentFamily();
        $this->assertSame(AccountType::Bachelor, $family->account_type);
        $this->assertTrue($family->isBachelor());
    }

    public function test_equal_shares_divide_exactly_with_no_remainder(): void
    {
        $shares = app(SplitExpenseService::class)->equalShares('20000', 4);

        $this->assertSame(['5000.00', '5000.00', '5000.00', '5000.00'], $shares);
        $this->assertSame(20000.0, array_sum(array_map('floatval', $shares)));
    }

    public function test_equal_shares_distribute_leftover_paise_and_still_reconcile(): void
    {
        // 100 / 3 = 33.34 + 33.33 + 33.33 = 100.00 exactly.
        $shares = app(SplitExpenseService::class)->equalShares('100', 3);

        $this->assertSame(['33.34', '33.33', '33.33'], $shares);
        $this->assertSame(100.0, round(array_sum(array_map('floatval', $shares)), 2));
    }

    public function test_recording_a_split_creates_a_settled_payer_share_and_owed_shares(): void
    {
        [$owner, $family, $members] = $this->bachelorAccount(3); // 4 members total

        app(SplitExpenseService::class)->record($family, $owner, $owner, [
            'title' => 'Room Rent',
            'total_amount' => '20000',
            'period' => '2026-07-01',
        ]);

        // One share per member, summing to the total.
        $this->assertSame(4, SplitShare::count());
        $this->assertSame('20000.00', (string) number_format((float) SplitShare::sum('amount'), 2, '.', ''));

        // Payer's own share is pre-settled; the other three still owe.
        $payerShare = SplitShare::where('user_id', $owner->id)->first();
        $this->assertNotNull($payerShare->settled_at);
        $this->assertSame(3, SplitShare::whereNull('settled_at')->count());
        $this->assertSame('5000.00', $payerShare->amount);
    }

    public function test_a_member_share_can_be_settled_via_the_route(): void
    {
        [$owner, $family, $members] = $this->bachelorAccount(1); // owner + 1
        app(SplitExpenseService::class)->record($family, $owner, $owner, [
            'title' => 'Internet', 'total_amount' => '1000', 'period' => '2026-07-01',
        ]);

        $share = SplitShare::whereNull('settled_at')->first();

        $this->actingAs($owner)->post(route('bachelor.splits.settle', $share))->assertRedirect();
        $this->assertNotNull($share->fresh()->settled_at);
    }

    public function test_the_owner_can_add_a_roommate_with_a_login(): void
    {
        [$owner, $family] = $this->bachelorAccount(0);

        $this->actingAs($owner)->post(route('bachelor.members.store'), [
            'name' => 'Roommate',
            'email' => 'mate@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ])->assertRedirect(route('bachelor.members.index'));

        $mate = User::firstWhere('email', 'mate@example.com');
        $this->assertNotNull($mate);
        $this->assertTrue($family->users()->whereKey($mate->id)->exists());
    }

    public function test_a_non_bachelor_account_cannot_reach_split_routes(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user, null, AccountType::Family);

        $this->actingAs($user)->get(route('bachelor.splits.index'))->assertForbidden();
        $this->actingAs($user)->get(route('bachelor.members.index'))->assertForbidden();
    }
}
