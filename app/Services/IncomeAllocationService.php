<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Family;
use App\Models\Income;
use App\Models\Loan;
use App\Models\LoanEmi;
use App\Models\LoanPayment;
use App\Models\Saving;
use Illuminate\Support\Carbon;

/**
 * Works out where a family's income went over a period: what share was spent on
 * expenses, paid toward loans/EMIs, moved into savings, and left over — and the
 * headline savings rate (the portion of income that wasn't consumed).
 */
class IncomeAllocationService
{
    /**
     * @return array{
     *     income: float, expenses: float, loans: float, savingsDeposits: float,
     *     leftover: float, saved: float, savingsRate: float, expenseRate: float,
     *     loanRate: float, depositRate: float, leftoverRate: float
     * }
     */
    public function forPeriod(Family $family, Carbon $start, Carbon $end): array
    {
        $income = (float) Income::query()->forFamily($family->id)
            ->whereBetween('income_date', [$start, $end])->sum('amount');

        $expenses = (float) Expense::query()->forFamily($family->id)
            ->whereBetween('expense_date', [$start, $end])->sum('amount');

        $loans = $this->loanOutflow($family, $start, $end);

        $savingsDeposits = (float) Saving::query()->forFamily($family->id)
            ->whereBetween('date', [$start, $end])->sum('amount');

        // Whatever income wasn't spent on expenses, loans, or moved to savings.
        $leftover = $income - $expenses - $loans - $savingsDeposits;

        // "Saved" = money kept rather than consumed by expenses or debt: the
        // deliberate savings deposits plus the unallocated leftover.
        $saved = $savingsDeposits + $leftover;

        $pct = static fn (float $part): float => $income > 0.0 ? round($part / $income * 100, 1) : 0.0;

        return [
            'income' => $income,
            'expenses' => $expenses,
            'loans' => $loans,
            'savingsDeposits' => $savingsDeposits,
            'leftover' => $leftover,
            'saved' => $saved,
            'savingsRate' => $pct($saved),
            'expenseRate' => $pct($expenses),
            'loanRate' => $pct($loans),
            'depositRate' => $pct($savingsDeposits),
            'leftoverRate' => $pct($leftover),
        ];
    }

    /**
     * Money paid toward loans in the period: manual repayments (loan_payments)
     * plus EMI installments marked paid (loan_emis). A loan uses one mechanism
     * or the other, so the two never double-count the same rupee.
     */
    private function loanOutflow(Family $family, Carbon $start, Carbon $end): float
    {
        $loanIds = Loan::query()->forFamily($family->id)->pluck('id');

        if ($loanIds->isEmpty()) {
            return 0.0;
        }

        $manual = (float) LoanPayment::query()->whereIn('loan_id', $loanIds)
            ->whereBetween('payment_date', [$start, $end])->sum('amount');

        $emis = (float) LoanEmi::query()->whereIn('loan_id', $loanIds)
            ->whereNotNull('paid_on')
            ->whereBetween('paid_on', [$start, $end])->sum('emi_amount');

        return $manual + $emis;
    }
}
