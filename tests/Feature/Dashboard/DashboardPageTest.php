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
            ->assertSee('This Month Income')
            ->assertSee('Total Loan Balance');
    }
}
