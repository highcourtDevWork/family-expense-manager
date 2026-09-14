<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\Saving;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * A single day's activity: totals plus the individual expense and income
     * rows for that date.
     *
     * @return array{
     *     date: Carbon, income: string, expense: string, balance: string,
     *     expenses: Collection<int, Expense>, incomes: Collection<int, Income>
     * }
     */
    public function daily(Family $family, ?Carbon $date = null): array
    {
        $date ??= Carbon::today();

        $expenses = Expense::query()->forFamily($family->id)
            ->with(['category', 'user'])
            ->whereDate('expense_date', $date)
            ->orderByDesc('id')
            ->get();

        $incomes = Income::query()->forFamily($family->id)
            ->with(['source', 'user'])
            ->whereDate('income_date', $date)
            ->orderByDesc('id')
            ->get();

        $incomeTotal = (float) $incomes->sum('amount');
        $expenseTotal = (float) $expenses->sum('amount');

        return [
            'date' => $date->copy(),
            'income' => (string) $incomeTotal,
            'expense' => (string) $expenseTotal,
            'balance' => (string) ($incomeTotal - $expenseTotal),
            'expenses' => $expenses,
            'incomes' => $incomes,
        ];
    }

    /**
     * A full combined monthly report: income, expense, balance, savings added
     * this month, and the current loan/debt positions, plus the by-category and
     * by-source breakdowns. Shared by the on-screen report, the PDF, and the CSV
     * export so they can never disagree.
     *
     * @return array{
     *     month: Carbon, income: string, expense: string, balance: string,
     *     savings: string, loanBalance: string, debtOwed: string,
     *     expensesByCategory: Collection<int, array{name: string, total: float}>,
     *     incomeBySource: Collection<int, array{name: string, total: float}>
     * }
     */
    public function monthly(Family $family, ?Carbon $month = null): array
    {
        $month ??= Carbon::now()->startOfMonth();
        [$start, $end] = [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];

        $summary = $this->dashboard->summary($family, $month);

        return [
            'month' => $month->copy()->startOfMonth(),
            'income' => $summary['income'],
            'expense' => $summary['expense'],
            'balance' => $summary['balance'],
            'savings' => (string) Saving::query()->forFamily($family->id)
                ->whereBetween('date', [$start, $end])->sum('amount'),
            'loanBalance' => $this->dashboard->totalLoanBalance($family),
            'debtOwed' => $this->dashboard->netDebtOwed($family),
            'expensesByCategory' => $this->dashboard->spendingByCategory($family, $month),
            'incomeBySource' => $this->incomeBySource($family, $start, $end),
        ];
    }

    /**
     * A year at a glance: a month-by-month income/expense/balance breakdown
     * plus year totals and the current savings/loan/debt positions.
     *
     * @return array{
     *     year: int, months: Collection<int, array{label: string, income: float, expense: float, balance: float}>,
     *     totalIncome: string, totalExpense: string, totalBalance: string,
     *     savings: string, loanBalance: string, debtOwed: string
     * }
     */
    public function yearly(Family $family, ?int $year = null): array
    {
        $year ??= (int) Carbon::now()->format('Y');

        $incomeByMonth = $this->monthlySums(Income::query()->forFamily($family->id), 'income_date', $year);
        $expenseByMonth = $this->monthlySums(Expense::query()->forFamily($family->id), 'expense_date', $year);

        $months = collect(range(1, 12))->map(function (int $m) use ($incomeByMonth, $expenseByMonth): array {
            $income = (float) ($incomeByMonth[$m] ?? 0);
            $expense = (float) ($expenseByMonth[$m] ?? 0);

            return [
                'label' => Carbon::create(null, $m, 1)->format('M'),
                'income' => $income,
                'expense' => $expense,
                'balance' => $income - $expense,
            ];
        });

        return [
            'year' => $year,
            'months' => $months,
            'totalIncome' => (string) $months->sum('income'),
            'totalExpense' => (string) $months->sum('expense'),
            'totalBalance' => (string) $months->sum('balance'),
            'savings' => $this->dashboard->totalSavings($family),
            'loanBalance' => $this->dashboard->totalLoanBalance($family),
            'debtOwed' => $this->dashboard->netDebtOwed($family),
        ];
    }

    /**
     * Sum a dated amount column per month-number (1–12) for a given year.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array<int, float>
     */
    private function monthlySums($query, string $dateColumn, int $year): array
    {
        return $query
            ->whereYear($dateColumn, $year)
            ->selectRaw("MONTH($dateColumn) as m, SUM(amount) as total")
            ->groupBy('m')
            ->pluck('total', 'm')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }

    /**
     * Income totalled per source between two dates, largest first.
     *
     * @return Collection<int, array{name: string, total: float}>
     */
    private function incomeBySource(Family $family, Carbon $start, Carbon $end): Collection
    {
        return Income::query()->forFamily($family->id)
            ->with('source')
            ->whereBetween('income_date', [$start, $end])
            ->selectRaw('income_source_id, SUM(amount) as total')
            ->groupBy('income_source_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Income $row): array => [
                'name' => $row->source?->name ?? 'Unknown',
                'total' => (float) $row->total,
            ]);
    }
}
