<?php

namespace Tests\Feature\Loan;

use App\Models\Loan;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_loan_index_and_show_pages_render(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        $loan = Loan::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('loans.index'))->assertOk()->assertSee($loan->loan_name);
        $this->actingAs($user)->get(route('loans.show', $loan))->assertOk()->assertSee('Payment History');
    }
}
