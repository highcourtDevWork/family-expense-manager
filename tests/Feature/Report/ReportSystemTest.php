<?php

namespace Tests\Feature\Report;

use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\Saving;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportSystemTest extends TestCase
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

    public function test_report_pages_render(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)->get(route('reports.index'))->assertOk();
        $this->actingAs($user)->get(route('reports.daily'))->assertOk();
        $this->actingAs($user)->get(route('reports.monthly'))->assertOk();
        $this->actingAs($user)->get(route('reports.yearly'))->assertOk();
    }

    public function test_the_daily_report_only_includes_that_day(): void
    {
        [$user, $family] = $this->userWithFamily();
        $category = $family->categories()->first();

        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-10', 'amount' => 500, 'description' => 'On the day',
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-11', 'amount' => 900, 'description' => 'Other day',
        ]);

        $report = app(ReportService::class)->daily($family, Carbon::parse('2026-07-10'));

        $this->assertSame('500', $report['expense']);
        $this->assertCount(1, $report['expenses']);
    }

    public function test_the_monthly_report_combines_all_modules(): void
    {
        [$user, $family] = $this->userWithFamily();
        $category = $family->categories()->first();
        $source = $family->incomeSources()->first();

        Income::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => $source->id,
            'income_date' => '2026-07-03', 'amount' => 50000,
        ]);
        Expense::factory()->for($family)->create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'expense_date' => '2026-07-08', 'amount' => 20000,
        ]);
        Saving::factory()->for($family)->create([
            'user_id' => $user->id, 'date' => '2026-07-09', 'amount' => 8000,
        ]);

        $report = app(ReportService::class)->monthly($family, Carbon::parse('2026-07-01'));

        $this->assertSame('50000', $report['income']);
        $this->assertSame('20000', $report['expense']);
        $this->assertSame('30000', $report['balance']);
        $this->assertSame('8000', $report['savings']);
    }

    public function test_the_yearly_report_breaks_down_by_month(): void
    {
        [$user, $family] = $this->userWithFamily();
        $source = $family->incomeSources()->first();

        Income::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => $source->id,
            'income_date' => '2026-02-10', 'amount' => 10000,
        ]);
        Income::factory()->for($family)->create([
            'user_id' => $user->id, 'income_source_id' => $source->id,
            'income_date' => '2026-05-10', 'amount' => 15000,
        ]);

        $report = app(ReportService::class)->yearly($family, 2026);

        $this->assertCount(12, $report['months']);
        $this->assertSame('25000', $report['totalIncome']);
        $this->assertSame(10000.0, $report['months']->firstWhere('label', 'Feb')['income']);
    }

    public function test_the_monthly_pdf_downloads(): void
    {
        [$user] = $this->userWithFamily();

        $response = $this->actingAs($user)->get(route('reports.monthly.pdf', ['month' => '2026-07']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_monthly_csv_downloads(): void
    {
        [$user] = $this->userWithFamily();

        $response = $this->actingAs($user)->get(route('reports.monthly.csv', ['month' => '2026-07']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Total Income', $response->streamedContent());
    }

    public function test_the_yearly_csv_downloads(): void
    {
        [$user] = $this->userWithFamily();

        $response = $this->actingAs($user)->get(route('reports.yearly.csv', ['year' => '2026']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('TOTAL', $response->streamedContent());
    }
}
