<?php

namespace Tests\Feature\Report;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_download_the_report(): void
    {
        $this->get(route('reports.monthly.pdf'))->assertRedirect(route('login'));
    }

    public function test_the_monthly_report_downloads_as_a_pdf(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        Income::factory()->for($family)->create([
            'user_id' => $user->id,
            'income_source_id' => $family->incomeSources()->first()->id,
            'income_date' => '2026-07-04',
            'amount' => 60000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id,
            'expense_category_id' => $family->categories()->first()->id,
            'expense_date' => '2026-07-08',
            'amount' => 15000,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.monthly.pdf', ['month' => '2026-07']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        // A valid PDF stream begins with the "%PDF" magic bytes.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_a_malformed_month_falls_back_to_the_current_month(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);

        $this->actingAs($user)
            ->get(route('reports.monthly.pdf', ['month' => 'not-a-month']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
