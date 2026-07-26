<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DebtService
{
    /**
     * Create a debt. It starts fully unpaid, so balance == amount.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Family $family, User $user, array $data): Debt
    {
        return $family->debts()->create([
            ...$data,
            'user_id' => $user->id,
            'paid_amount' => 0,
            'balance_amount' => $data['amount'],
            'status' => DebtStatus::Open,
        ]);
    }

    /**
     * Update the debt's own fields, then recalculate — the amount may have
     * changed, which shifts the balance and possibly the status.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Debt $debt, array $data): Debt
    {
        $debt->update($data);

        return $this->recalculate($debt);
    }

    /**
     * Record a repayment against the debt and refresh its derived totals.
     *
     * @param  array<string, mixed>  $data
     */
    public function addPayment(Debt $debt, array $data): DebtPayment
    {
        return DB::transaction(function () use ($debt, $data): DebtPayment {
            $payment = $debt->payments()->create($data);

            $this->recalculate($debt);

            return $payment;
        });
    }

    public function deletePayment(DebtPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $debt = $payment->debt;
            $payment->delete();

            $this->recalculate($debt);
        });
    }

    public function delete(Debt $debt): void
    {
        $debt->delete();
    }

    /**
     * The single source of truth for a debt's derived figures: sum the
     * payments, derive the balance (never negative), and flip the status to
     * Settled once it is fully paid. Called after every change so
     * paid_amount / balance_amount / status can never drift out of sync.
     */
    public function recalculate(Debt $debt): Debt
    {
        $paid = (float) $debt->payments()->sum('amount');
        $balance = max((float) $debt->amount - $paid, 0);

        $debt->update([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'status' => $balance <= 0 ? DebtStatus::Settled : DebtStatus::Open,
        ]);

        return $debt;
    }
}
