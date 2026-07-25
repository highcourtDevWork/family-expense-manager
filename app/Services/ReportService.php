<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Income;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * A full monthly financial report for a family: totals plus the
     * expense-by-category and income-by-source breakdowns. Shared by the
     * on-screen report and the PDF export so both always agree.
     *
     * @return array{
     *     month: Carbon,
     *     income: string,
     *     expense: string,
     *     balance: string,
     *     expensesByCategory: Collection<int, array{name: string, total: float}>,
     *     incomeBySource: Collection<int, array{name: string, total: float}>
     * }
     */
    public function monthly(Family $family, ?Carbon $month = null): array
    {
        $month ??= Carbon::now()->startOfMonth();

        $summary = $this->dashboard->summary($family, $month);

        return [
            'month' => $month->copy()->startOfMonth(),
            'income' => $summary['income'],
            'expense' => $summary['expense'],
            'balance' => $summary['balance'],
            'expensesByCategory' => $this->dashboard->spendingByCategory($family, $month),
            'incomeBySource' => $this->incomeBySource($family, $month),
        ];
    }

    /**
     * Income totalled per source for the month, largest first.
     *
     * @return Collection<int, array{name: string, total: float}>
     */
    private function incomeBySource(Family $family, Carbon $month): Collection
    {
        return Income::query()->forFamily($family->id)
            ->with('source')
            ->whereBetween('income_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
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
