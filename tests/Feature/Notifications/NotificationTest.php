<?php

namespace Tests\Feature\Notifications;

use App\Enums\LoanStatus;
use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanPaymentReminder;
use App\Notifications\MonthlyExpenseWarning;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_loan_reminders_go_to_holders_of_loans_due_soon(): void
    {
        Notification::fake();
        [$user, $family] = $this->userWithFamily();

        // Due in 3 days with a balance -> should remind.
        $dueSoon = Loan::factory()->for($family)->create([
            'user_id' => $user->id, 'status' => LoanStatus::Active,
            'remaining_amount' => 5000, 'end_date' => '2026-07-18',
        ]);
        // Due far in the future -> should NOT remind.
        Loan::factory()->for($family)->create([
            'user_id' => $user->id, 'status' => LoanStatus::Active,
            'remaining_amount' => 5000, 'end_date' => '2026-12-01',
        ]);

        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertSentTo($user, LoanPaymentReminder::class, function ($n) use ($dueSoon) {
            return $n->loan->is($dueSoon);
        });
        Notification::assertSentToTimes($user, LoanPaymentReminder::class, 1);
    }

    public function test_no_reminder_for_fully_paid_loans(): void
    {
        Notification::fake();
        [$user, $family] = $this->userWithFamily();

        Loan::factory()->for($family)->create([
            'user_id' => $user->id, 'status' => LoanStatus::Closed,
            'remaining_amount' => 0, 'end_date' => '2026-07-16',
        ]);

        $this->artisan('loans:remind')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_expense_warning_is_sent_to_admins_when_overspending(): void
    {
        Notification::fake();
        [$admin, $family] = $this->userWithFamily();
        $source = $family->incomeSources()->first();
        $category = $family->categories()->first();

        Income::factory()->for($family)->create([
            'user_id' => $admin->id, 'income_source_id' => $source->id,
            'income_date' => '2026-07-05', 'amount' => 10000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $admin->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-06', 'amount' => 25000,
        ]);

        $this->artisan('expenses:warn')->assertSuccessful();

        Notification::assertSentTo($admin, MonthlyExpenseWarning::class);
    }

    public function test_no_warning_when_spending_is_within_income(): void
    {
        Notification::fake();
        [$admin, $family] = $this->userWithFamily();
        $source = $family->incomeSources()->first();
        $category = $family->categories()->first();

        Income::factory()->for($family)->create([
            'user_id' => $admin->id, 'income_source_id' => $source->id,
            'income_date' => '2026-07-05', 'amount' => 50000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $admin->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-06', 'amount' => 10000,
        ]);

        $this->artisan('expenses:warn')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_a_user_can_view_and_clear_notifications(): void
    {
        [$user, $family] = $this->userWithFamily();
        $loan = Loan::factory()->for($family)->create([
            'user_id' => $user->id, 'status' => LoanStatus::Active,
            'remaining_amount' => 5000, 'end_date' => '2026-07-18',
        ]);
        $user->notify(new LoanPaymentReminder($loan));

        $this->assertSame(1, $user->unreadNotifications()->count());

        // Viewing marks them read.
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
        $this->assertSame(0, $user->refresh()->unreadNotifications()->count());

        // Clearing removes them.
        $this->actingAs($user)->delete(route('notifications.destroy'))->assertRedirect();
        $this->assertSame(0, $user->refresh()->notifications()->count());
    }
}
