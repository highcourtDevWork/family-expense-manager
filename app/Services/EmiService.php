<?php

namespace App\Services;

use App\Enums\EmiStatus;
use App\Models\Loan;
use App\Models\LoanEmi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmiService
{
    public function __construct(
        private readonly LoanService $loanService,
    ) {}

    /**
     * Standard reducing-balance EMI for a principal over N months at an annual
     * percentage rate. With zero interest it is simply principal / months.
     */
    public function calculateEmi(float $principal, float $annualRatePercent, int $months): float
    {
        if ($months <= 0) {
            return 0.0;
        }

        if ($annualRatePercent <= 0) {
            return round($principal / $months, 2);
        }

        $i = $annualRatePercent / 12 / 100;
        $factor = (1 + $i) ** $months;

        return round($principal * $i * $factor / ($factor - 1), 2);
    }

    /**
     * Compute the amortization schedule for a loan without persisting it — pure
     * and unit-testable. Each row splits the EMI into interest (on the reducing
     * balance) and principal; the final installment absorbs rounding so the
     * balance lands exactly on zero.
     *
     * @return list<array<string, mixed>>
     */
    public function buildSchedule(Loan $loan): array
    {
        $months = (int) $loan->tenure_months;
        $principalTotal = (float) $loan->total_amount;
        $annual = (float) ($loan->interest_rate ?? 0);
        $monthlyRate = $annual > 0 ? $annual / 12 / 100 : 0.0;

        $emi = $loan->emi_amount !== null
            ? (float) $loan->emi_amount
            : $this->calculateEmi($principalTotal, $annual, $months);

        $rows = [];
        $balance = $principalTotal;

        for ($k = 1; $k <= $months; $k++) {
            $interest = round($balance * $monthlyRate, 2);
            $principal = round($emi - $interest, 2);

            // Last installment, or one that would overshoot, clears the balance.
            if ($k === $months || $principal >= $balance) {
                $principal = round($balance, 2);
                $installment = round($principal + $interest, 2);
            } else {
                $installment = $emi;
            }

            $closing = round($balance - $principal, 2);

            $rows[] = [
                'sequence' => $k,
                'due_date' => $this->dueDateFor($loan->start_date, (int) $loan->emi_due_day, $k),
                'emi_amount' => $installment,
                'principal_component' => $principal,
                'interest_component' => $interest,
                'opening_balance' => round($balance, 2),
                'closing_balance' => max($closing, 0),
                'status' => EmiStatus::Pending->value,
            ];

            $balance = $closing;

            if ($balance <= 0) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Persist a freshly built schedule for the loan. Only regenerates while no
     * installment has been paid yet, so posted history is never destroyed.
     */
    public function generateSchedule(Loan $loan): void
    {
        if (! $loan->is_emi) {
            return;
        }

        DB::transaction(function () use ($loan): void {
            if ($loan->emis()->where('status', EmiStatus::Paid)->exists()) {
                return; // config is locked once repayment has started
            }

            $loan->emis()->delete();

            $now = now();
            $rows = array_map(fn (array $row): array => [
                ...$row,
                'loan_id' => $loan->id,
                'created_at' => $now,
                'updated_at' => $now,
            ], $this->buildSchedule($loan));

            LoanEmi::insert($rows);

            $this->loanService->recalculate($loan->refresh());
        });
    }

    /**
     * Auto-post every EMI installment that is due on or before the given date:
     * mark it paid and refresh its loan's derived totals. This is what the
     * scheduled command runs so records appear without manual entry.
     *
     * @return int number of installments posted
     */
    public function postDueInstallments(?Carbon $asOf = null): int
    {
        $asOf ??= Carbon::today();

        $due = LoanEmi::query()
            ->where('status', EmiStatus::Pending)
            ->whereDate('due_date', '<=', $asOf)
            ->with('loan')
            ->get();

        $posted = 0;

        foreach ($due->groupBy('loan_id') as $installments) {
            DB::transaction(function () use ($installments, &$posted): void {
                foreach ($installments as $emi) {
                    $emi->update(['status' => EmiStatus::Paid, 'paid_on' => $emi->due_date]);
                    $posted++;
                }

                $this->loanService->recalculate($installments->first()->loan);
            });
        }

        return $posted;
    }

    /**
     * Manually mark a single installment as paid (dated today) and refresh the
     * loan's derived totals. Used by the "Mark as paid" action.
     */
    public function markPaid(LoanEmi $emi): void
    {
        if ($emi->status === EmiStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($emi): void {
            $emi->update(['status' => EmiStatus::Paid, 'paid_on' => Carbon::today()]);

            $this->loanService->recalculate($emi->loan);
        });
    }

    /**
     * Revert an installment back to pending (undo an accidental payment) and
     * refresh the loan's derived totals.
     */
    public function markPending(LoanEmi $emi): void
    {
        if ($emi->status === EmiStatus::Pending) {
            return;
        }

        DB::transaction(function () use ($emi): void {
            $emi->update(['status' => EmiStatus::Pending, 'paid_on' => null]);

            $this->loanService->recalculate($emi->loan);
        });
    }

    /**
     * Display figures for an EMI loan, derived from its schedule.
     *
     * @return array{
     *     totalPayable: float, totalPaid: float, paidCount: int, pendingCount: int,
     *     outstandingPrincipal: float, nextDueDate: ?Carbon
     * }
     */
    public function stats(Loan $loan): array
    {
        $emis = $loan->emis;
        $paid = $emis->where('status', EmiStatus::Paid);

        return [
            'totalPayable' => (float) $emis->sum('emi_amount'),
            'totalPaid' => (float) $paid->sum('emi_amount'),
            'paidCount' => $paid->count(),
            'pendingCount' => $emis->count() - $paid->count(),
            'outstandingPrincipal' => round((float) $loan->total_amount - (float) $paid->sum('principal_component'), 2),
            'nextDueDate' => $emis->firstWhere('status', EmiStatus::Pending)?->due_date,
        ];
    }

    /**
     * The due date for installment $k: $k months after the start date, on the
     * configured day of month (clamped to the month's length).
     */
    private function dueDateFor(Carbon $start, int $dueDay, int $k): Carbon
    {
        $date = $start->copy()->addMonthsNoOverflow($k);

        return $date->day(min(max($dueDay, 1), $date->daysInMonth));
    }
}
