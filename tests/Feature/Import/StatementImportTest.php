<?php

namespace Tests\Feature\Import;

use App\Enums\PaymentMethod;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\GpayStatementParser;
use App\Support\ParsedTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StatementImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<ParsedTransaction>
     */
    private function sampleTransactions(): array
    {
        return [
            new ParsedTransaction('expense', '2026-06-14', 'APOLLO PHARMACY', '111', 349.0),
            new ParsedTransaction('expense', '2026-06-05', 'HOTEL KALAISELVI', '222', 115.0),
            new ParsedTransaction('income', '2026-06-03', 'K.P MANIKANDAN', '333', 4000.0),
        ];
    }

    /**
     * Bind a parser stub so the controller never touches a real PDF; the
     * upload only needs to satisfy validation.
     */
    private function fakeParserReturns(array $transactions): void
    {
        $this->mock(GpayStatementParser::class, function ($mock) use ($transactions) {
            $mock->shouldReceive('parseFile')->andReturn($transactions);
        });
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf');
    }

    public function test_guests_cannot_reach_the_importer(): void
    {
        $this->get(route('imports.create'))->assertRedirect(route('login'));
    }

    public function test_upload_parses_and_shows_a_preview_without_saving(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);
        $this->fakeParserReturns($this->sampleTransactions());

        $this->actingAs($user)
            ->post(route('imports.parse'), ['statement' => $this->pdf()])
            ->assertRedirect(route('imports.preview'));

        // Preview renders and nothing has been written yet.
        $this->actingAs($user)->get(route('imports.preview'))
            ->assertOk()
            ->assertSee('APOLLO PHARMACY')
            ->assertSee('K.P MANIKANDAN');

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('incomes', 0);
    }

    public function test_confirm_imports_expenses_and_income_with_guessed_categories(): void
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);
        $this->fakeParserReturns($this->sampleTransactions());

        $this->actingAs($user)->post(route('imports.parse'), ['statement' => $this->pdf()]);

        $this->actingAs($user)
            ->post(route('imports.confirm'))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseCount('incomes', 1);

        // "APOLLO PHARMACY" was guessed as Medical; UPI payment method recorded.
        $medical = $family->categories()->firstWhere('name', 'Medical');
        $this->assertDatabaseHas('expenses', [
            'reference' => '111',
            'expense_category_id' => $medical->id,
            'payment_method' => PaymentMethod::Upi->value,
            'amount' => 349.0,
        ]);

        // "HOTEL KALAISELVI" was guessed as Food.
        $food = $family->categories()->firstWhere('name', 'Food');
        $this->assertDatabaseHas('expenses', ['reference' => '222', 'expense_category_id' => $food->id]);

        $this->assertDatabaseHas('incomes', ['reference' => '333', 'amount' => 4000.0]);
    }

    public function test_re_importing_the_same_statement_skips_duplicates(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);
        $this->fakeParserReturns($this->sampleTransactions());

        // First import.
        $this->actingAs($user)->post(route('imports.parse'), ['statement' => $this->pdf()]);
        $this->actingAs($user)->post(route('imports.confirm'));

        // Second import of the same statement.
        $this->actingAs($user)->post(route('imports.parse'), ['statement' => $this->pdf()]);
        $this->actingAs($user)->post(route('imports.confirm'));

        // No duplicates created.
        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseCount('incomes', 1);
    }

    public function test_an_empty_parse_returns_an_error(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);
        $this->fakeParserReturns([]);

        $this->actingAs($user)
            ->post(route('imports.parse'), ['statement' => $this->pdf()])
            ->assertSessionHasErrors('statement');
    }

    public function test_the_upload_must_be_a_pdf(): void
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);

        $this->actingAs($user)
            ->post(route('imports.parse'), ['statement' => UploadedFile::fake()->create('note.txt', 10, 'text/plain')])
            ->assertSessionHasErrors('statement');
    }
}
