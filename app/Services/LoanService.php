<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Models\Family;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a loan. It starts fully unpaid, so remaining == total.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Family $family, User $user, array $data): Loan
    {
        return $family->loans()->create([
            ...$data,
            'user_id' => $user->id,
            'paid_amount' => 0,
            'remaining_amount' => $data['total_amount'],
            'status' => LoanStatus::Active,
        ]);
    }

    /**
     * Update the loan's own fields, then recalculate — the total may have
     * changed, which shifts the remaining balance and possibly the status.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Loan $loan, array $data): Loan
    {
        $loan->update($data);

        return $this->recalculate($loan);
    }

    /**
     * Record a repayment against the loan and refresh its derived totals.
     *
     * @param  array<string, mixed>  $data
     */
    public function addPayment(Loan $loan, array $data): LoanPayment
    {
        return DB::transaction(function () use ($loan, $data): LoanPayment {
            $payment = $loan->payments()->create($data);

            $this->recalculate($loan);

            return $payment;
        });
    }

    /**
     * Remove a repayment and refresh the loan's derived totals.
     */
    public function deletePayment(LoanPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $loan = $payment->loan;
            $payment->delete();

            $this->recalculate($loan);
        });
    }

    public function delete(Loan $loan): void
    {
        $loan->delete();
    }

    /**
     * The single source of truth for a loan's derived figures: sum the
     * payments, derive the remaining balance (never negative), and flip the
     * status to Closed once it is fully repaid. Called after every change so
     * paid_amount / remaining_amount / status can never drift out of sync.
     */
    public function recalculate(Loan $loan): Loan
    {
        $paid = (float) $loan->payments()->sum('amount');
        $remaining = max((float) $loan->total_amount - $paid, 0);

        $loan->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $remaining <= 0 ? LoanStatus::Closed : LoanStatus::Active,
        ]);

        return $loan;
    }
}
