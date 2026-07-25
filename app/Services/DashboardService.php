<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\Loan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * The summary cards shown at the top of the dashboard for a given month.
     *
     * @return array{income: string, expense: string, balance: string, month: Carbon}
     */
    public function summary(Family $family, ?Carbon $month = null): array
    {
        $month ??= Carbon::now()->startOfMonth();
        [$start, $end] = $this->monthRange($month);

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
            'month' => $month->copy(),
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
     * Category-wise spending for a month: [{name, total}], largest first.
     * Feeds the category pie/donut chart.
     *
     * @return Collection<int, array{name: string, total: float}>
     */
    public function spendingByCategory(Family $family, ?Carbon $month = null): Collection
    {
        $month ??= Carbon::now()->startOfMonth();
        [$start, $end] = $this->monthRange($month);

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
    public function monthlyTrend(Family $family, int $months = 6): Collection
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

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

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function monthRange(Carbon $month): array
    {
        return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];
    }
}
