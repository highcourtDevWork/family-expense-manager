<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Support\Carbon;

class RecurringExpenseService
{
    /**
     * Post a template as a real expense for the given month, dated the 1st.
     * Idempotent: the expense carries a "recurring:{id}:{YYYY-MM}" reference, so
     * calling this again for the same month returns null instead of duplicating.
     */
    public function postForMonth(RecurringExpense $template, ?Carbon $month = null): ?Expense
    {
        $month ??= Carbon::now();
        $reference = $template->referenceFor($month);

        $alreadyPosted = Expense::query()
            ->where('family_id', $template->family_id)
            ->where('reference', $reference)
            ->exists();

        if ($alreadyPosted) {
            return null;
        }

        return $template->family->expenses()->create([
            'user_id' => $template->user_id,
            'expense_category_id' => $this->resolveCategoryId($template),
            'expense_date' => $month->copy()->startOfMonth(),
            'amount' => $template->amount,
            'payment_method' => $template->payment_method,
            'description' => $template->name,
            'reference' => $reference,
        ]);
    }

    /**
     * Post every active, auto-post template for the given month. Used by the
     * monthly scheduler. Returns how many new expenses were created.
     */
    public function postAllDue(?Carbon $month = null): int
    {
        $month ??= Carbon::now();
        $created = 0;

        RecurringExpense::query()
            ->where('active', true)
            ->where('auto_post', true)
            ->with('family')
            ->chunk(100, function ($templates) use ($month, &$created): void {
                foreach ($templates as $template) {
                    if ($this->postForMonth($template, $month) !== null) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    /**
     * A template's category, or the family's "Others" category when none is set.
     */
    private function resolveCategoryId(RecurringExpense $template): int
    {
        return $template->expense_category_id
            ?? $template->family->categories()->firstOrCreate(['name' => 'Others'])->id;
    }
}
