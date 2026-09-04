<?php

namespace Tests\Feature\Dashboard;

use App\Models\Expense;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_dashboard_renders_for_a_family_member(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        Expense::factory()->for($family)->create([
            'user_id' => $user->id,
            'expense_category_id' => $family->categories()->first()->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('This month')        // default period preset in the filter
            ->assertSee('Total Loan Balance');
    }

    public function test_a_custom_date_range_scopes_the_summary(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        $category = $family->categories()->first();

        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-03-15', 'amount' => 700,
        ]);
        // Outside the requested window — must be excluded.
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-05-01', 'amount' => 999,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['period' => 'custom', 'date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
            ->assertOk()
            ->assertSee('01 Mar 2026 – 31 Mar 2026')
            ->assertSee('700');
    }
}
