<?php

namespace App\Services;

use App\Enums\DebtType;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\Loan;
use App\Models\Saving;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * The summary cards shown at the top of the dashboard for a date range.
     * With no range given it defaults to the current month. Loan/debt/savings
     * are point-in-time balances and are not range-scoped.
     *
     * @return array{income: string, expense: string, balance: string, loanBalance: string, debtOwed: string, savings: string, start: Carbon, end: Carbon, month: Carbon}
     */
    public function summary(Family $family, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $start ??= Carbon::now()->startOfMonth();
        $end ??= $start->copy()->endOfMonth();

        $income = Income::query()->forFamily($family->id)
            ->whereBetween('income_date', [$start, $end])
            ->sum('amount');

        $expense = Expense::query()->forFamily($family->id)
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        return [
            'income' => (string) $income,
            'expense' => (string) $expense,
            'balance' => (string) ((float) $income - (float) $expense),
            'loanBalance' => $this->totalLoanBalance($family),
            'debtOwed' => $this->netDebtOwed($family),
            'savings' => $this->totalSavings($family),
            'start' => $start->copy(),
            'end' => $end->copy(),
            // Kept for the API/back-compat; equals the range's starting month.
            'month' => $start->copy(),
        ];
    }

    /**
     * Outstanding balance across all of the family's loans. Reads the
     * denormalised remaining_amount that LoanService keeps in sync.
     */
    public function totalLoanBalance(Family $family): string
    {
        return (string) Loan::query()->forFamily($family->id)->sum('remaining_amount');
    }

    /**
     * Net amount the family owes: what it still owes on "borrowed" debts minus
     * what is still owed back to it on "lent" debts. Negative means others owe
     * the family more than it owes them.
     */
    public function netDebtOwed(Family $family): string
    {
        $borrowed = (float) Debt::query()->forFamily($family->id)
            ->where('debt_type', DebtType::Borrowed)->sum('balance_amount');

        $lent = (float) Debt::query()->forFamily($family->id)
            ->where('debt_type', DebtType::Lent)->sum('balance_amount');

        return (string) ($borrowed - $lent);
    }

    /**
     * Total of all recorded savings for the family.
     */
    public function totalSavings(Family $family): string
    {
        return (string) Saving::query()->forFamily($family->id)->sum('amount');
    }

    /**
     * Category-wise spending for a month: [{name, total}], largest first.
     * Feeds the category pie/donut chart.
     *
     * @return Collection<int, array{name: string, total: float}>
     */
    public function spendingByCategory(Family $family, ?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $start ??= Carbon::now()->startOfMonth();
        $end ??= $start->copy()->endOfMonth();

        return Expense::query()->forFamily($family->id)
            ->with('category')
            ->whereBetween('expense_date', [$start, $end])
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Expense $row): array => [
                'name' => $row->category?->name ?? 'Uncategorised',
                'total' => (float) $row->total,
            ]);
    }

    /**
     * Income vs. expense per month across the trailing N months (oldest first).
     * Feeds both the monthly-trend line chart and the income-vs-expense columns.
     *
     * @return Collection<int, array{label: string, income: float, expense: float}>
     */
    public function monthlyTrend(Family $family, int $months = 6, ?Carbon $endMonth = null): Collection
    {
        $anchor = ($endMonth ?? Carbon::now())->copy()->startOfMonth();
        $start = $anchor->copy()->subMonths($months - 1);

        // Pull raw monthly sums keyed by "YYYY-MM", then stitch a complete,
        // gap-free series so months with no activity still render as zero.
        $incomeByMonth = $this->sumByMonth(
            Income::query()->forFamily($family->id)
                ->where('income_date', '>=', $start)
                ->selectRaw("strftime('%Y-%m', income_date) as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym')
        );

        $expenseByMonth = $this->sumByMonth(
            Expense::query()->forFamily($family->id)
                ->where('expense_date', '>=', $start)
                ->selectRaw("strftime('%Y-%m', expense_date) as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym')
        );

        return collect(range(0, $months - 1))->map(function (int $offset) use ($start, $incomeByMonth, $expenseByMonth): array {
            $month = $start->copy()->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'label' => $month->format('M Y'),
                'income' => (float) ($incomeByMonth[$key] ?? 0),
                'expense' => (float) ($expenseByMonth[$key] ?? 0),
            ];
        });
    }

    /**
     * Normalise a pluck() result into a plain [ym => float] map.
     *
     * @param  Collection<string, mixed>  $raw
     * @return array<string, float>
     */
    private function sumByMonth(Collection $raw): array
    {
        return $raw->map(fn ($total): float => (float) $total)->all();
    }
}
