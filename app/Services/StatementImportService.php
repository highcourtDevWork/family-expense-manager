<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\ExpenseCategory;
use App\Models\Family;
use App\Models\IncomeSource;
use App\Models\User;
use App\Support\ParsedTransaction;
use Illuminate\Support\Facades\DB;

class StatementImportService
{
    /**
     * Keyword => category name. The counterparty text is scanned (case
     * insensitively) and the first hit wins; anything unmatched falls back to
     * "Others". All target categories exist in FamilyService::DEFAULT_CATEGORIES.
     *
     * @var array<string, string>
     */
    private const CATEGORY_KEYWORDS = [
        'hospital' => 'Medical',
        'pharmacy' => 'Medical',
        'clinic' => 'Medical',
        'jio' => 'Internet',
        'bsnl' => 'Internet',
        'airtel' => 'Internet',
        'prepaid' => 'Internet',
        'recharge' => 'Internet',
        'hotel' => 'Food',
        'bakery' => 'Food',
        'sweets' => 'Food',
        'restaurant' => 'Food',
        'pizza' => 'Food',
        'juice' => 'Food',
        'fruit' => 'Food',
        'cafe' => 'Food',
        'masanam' => 'Food',
        'flipkart' => 'Shopping',
        'amazon' => 'Shopping',
        'store' => 'Shopping',
        'mart' => 'Shopping',
        'traders' => 'Shopping',
        'supermarts' => 'Shopping',
        'fuel' => 'Travel',
        'petrol' => 'Travel',
        'diesel' => 'Travel',
    ];

    /**
     * Split a batch of parsed transactions into those that are new and those
     * already imported (matched on family + reference), without writing
     * anything. Powers the preview screen.
     *
     * @param  list<ParsedTransaction>  $transactions
     * @return array{new: list<ParsedTransaction>, duplicates: list<ParsedTransaction>}
     */
    public function partition(Family $family, array $transactions): array
    {
        $existing = $this->existingReferences($family, $transactions);
        $seen = [];
        $new = [];
        $duplicates = [];

        foreach ($transactions as $transaction) {
            $key = $transaction->type.':'.$transaction->reference;

            // Already in the database, or already appeared earlier in this file.
            if (isset($existing[$key]) || isset($seen[$key])) {
                $duplicates[] = $transaction;

                continue;
            }

            $seen[$key] = true;
            $new[] = $transaction;
        }

        return ['new' => $new, 'duplicates' => $duplicates];
    }

    /**
     * Import the given transactions, skipping any that already exist. Returns a
     * summary of what happened.
     *
     * @param  list<ParsedTransaction>  $transactions
     * @return array{expenses: int, incomes: int, skipped: int}
     */
    public function import(Family $family, User $user, array $transactions): array
    {
        $partitioned = $this->partition($family, $transactions);

        $expenses = 0;
        $incomes = 0;

        DB::transaction(function () use ($family, $user, $partitioned, &$expenses, &$incomes): void {
            foreach ($partitioned['new'] as $transaction) {
                if ($transaction->isExpense()) {
                    $this->createExpense($family, $user, $transaction);
                    $expenses++;
                } else {
                    $this->createIncome($family, $user, $transaction);
                    $incomes++;
                }
            }
        });

        return [
            'expenses' => $expenses,
            'incomes' => $incomes,
            'skipped' => count($partitioned['duplicates']),
        ];
    }

    private function createExpense(Family $family, User $user, ParsedTransaction $transaction): void
    {
        $family->expenses()->create([
            'user_id' => $user->id,
            'expense_category_id' => $this->resolveCategory($family, $transaction->counterparty)->id,
            'expense_date' => $transaction->date,
            'amount' => $transaction->amount,
            'payment_method' => PaymentMethod::Upi,
            'description' => 'Paid to '.$transaction->counterparty,
            'reference' => $transaction->reference,
        ]);
    }

    private function createIncome(Family $family, User $user, ParsedTransaction $transaction): void
    {
        $family->incomes()->create([
            'user_id' => $user->id,
            'income_source_id' => $this->resolveIncomeSource($family)->id,
            'income_date' => $transaction->date,
            'amount' => $transaction->amount,
            'notes' => 'Received from '.$transaction->counterparty,
            'reference' => $transaction->reference,
        ]);
    }

    /**
     * Pick a category for an expense from the counterparty text, defaulting to
     * "Others". Categories are looked up within the family so no cross-family
     * row is ever referenced.
     */
    private function resolveCategory(Family $family, string $counterparty): ExpenseCategory
    {
        $haystack = mb_strtolower($counterparty);
        $target = 'Others';

        foreach (self::CATEGORY_KEYWORDS as $keyword => $category) {
            if (str_contains($haystack, $keyword)) {
                $target = $category;

                break;
            }
        }

        return $family->categories()->firstOrCreate(['name' => $target]);
    }

    private function resolveIncomeSource(Family $family): IncomeSource
    {
        return $family->incomeSources()->firstOrCreate(['name' => 'Other']);
    }

    /**
     * Look up which of the batch's references already exist in the database,
     * in one query per type, keyed as "type:reference".
     *
     * @param  list<ParsedTransaction>  $transactions
     * @return array<string, true>
     */
    private function existingReferences(Family $family, array $transactions): array
    {
        $expenseRefs = [];
        $incomeRefs = [];

        foreach ($transactions as $transaction) {
            if ($transaction->isExpense()) {
                $expenseRefs[] = $transaction->reference;
            } else {
                $incomeRefs[] = $transaction->reference;
            }
        }

        $found = [];

        if ($expenseRefs !== []) {
            foreach ($family->expenses()->whereIn('reference', $expenseRefs)->pluck('reference') as $ref) {
                $found['expense:'.$ref] = true;
            }
        }

        if ($incomeRefs !== []) {
            foreach ($family->incomes()->whereIn('reference', $incomeRefs)->pluck('reference') as $ref) {
                $found['income:'.$ref] = true;
            }
        }

        return $found;
    }
}
