<?php

namespace App\Services;

use App\Models\Family;
use App\Models\SplitExpense;
use App\Models\SplitShare;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SplitExpenseService
{
    /**
     * Record a shared expense paid in full by one member and split equally
     * among all current members of the (bachelor) family.
     *
     * The split is computed in integer paise so it always reconciles exactly:
     * any leftover paise from an uneven division are handed to the earliest
     * members, so the shares sum back to the exact total. The payer's own
     * share is stored already settled — they fronted the money.
     *
     * @param  array<string, mixed>  $attributes  title, total_amount, period, expense_category_id?, notes?
     */
    public function record(Family $family, User $payer, User $creator, array $attributes): SplitExpense
    {
        return DB::transaction(function () use ($family, $payer, $creator, $attributes): SplitExpense {
            $members = $family->users()->orderBy('users.id')->get();

            $expense = $family->splitExpenses()->create([
                'paid_by' => $payer->id,
                'created_by' => $creator->id,
                'expense_category_id' => $attributes['expense_category_id'] ?? null,
                'title' => $attributes['title'],
                'total_amount' => $attributes['total_amount'],
                'period' => Carbon::parse($attributes['period'])->startOfMonth(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($this->equalShares((string) $attributes['total_amount'], $members->count()) as $index => $amount) {
                $member = $members[$index];

                SplitShare::create([
                    'family_id' => $family->id,
                    'split_expense_id' => $expense->id,
                    'user_id' => $member->id,
                    'amount' => $amount,
                    // The payer doesn't owe themselves.
                    'settled_at' => $member->id === $payer->id ? now() : null,
                ]);
            }

            return $expense;
        });
    }

    /**
     * Mark a member's share as settled (they've paid the payer back).
     */
    public function settle(SplitShare $share): void
    {
        if ($share->settled_at === null) {
            $share->forceFill(['settled_at' => now()])->save();
        }
    }

    /**
     * Reverse a settlement.
     */
    public function unsettle(SplitShare $share): void
    {
        if ($share->settled_at !== null) {
            $share->forceFill(['settled_at' => null])->save();
        }
    }

    /**
     * Divide a decimal amount into `$count` equal shares that sum back exactly
     * to the original, distributing any leftover paise to the first shares.
     *
     * @return list<string> each share as a 2-decimal string
     */
    public function equalShares(string $total, int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $totalPaise = (int) round(((float) $total) * 100);
        $base = intdiv($totalPaise, $count);
        $remainder = $totalPaise % $count;

        $shares = [];
        for ($i = 0; $i < $count; $i++) {
            $paise = $base + ($i < $remainder ? 1 : 0);
            $shares[] = number_format($paise / 100, 2, '.', '');
        }

        return $shares;
    }
}
