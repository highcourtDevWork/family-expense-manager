<?php

namespace App\Services;

use App\Models\Income;
use App\Models\RecurringIncome;
use Illuminate\Support\Carbon;

class RecurringIncomeService
{
    /**
     * Post a template as a real income for the given month, dated the 1st.
     * Idempotent: the income carries a "recurring-income:{id}:{YYYY-MM}"
     * reference, so calling this again for the same month returns null instead
     * of duplicating.
     */
    public function postForMonth(RecurringIncome $template, ?Carbon $month = null): ?Income
    {
        $month ??= Carbon::now();
        $reference = $template->referenceFor($month);

        $alreadyPosted = Income::query()
            ->where('family_id', $template->family_id)
            ->where('reference', $reference)
            ->exists();

        if ($alreadyPosted) {
            return null;
        }

        return $template->family->incomes()->create([
            // Attribute the income to the earning member, not whoever posts it.
            'user_id' => $template->user_id,
            'income_source_id' => $this->resolveSourceId($template),
            'income_date' => $month->copy()->startOfMonth(),
            'amount' => $template->amount,
            'notes' => $template->name,
            'reference' => $reference,
        ]);
    }

    /**
     * Post every active, auto-post template for the given month. Used by the
     * monthly scheduler. Returns how many new incomes were created.
     */
    public function postAllDue(?Carbon $month = null): int
    {
        $month ??= Carbon::now();
        $created = 0;

        RecurringIncome::query()
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
     * A template's source, or the family's "Salary" source when none is set.
     */
    private function resolveSourceId(RecurringIncome $template): int
    {
        return $template->income_source_id
            ?? $template->family->incomeSources()->firstOrCreate(['name' => 'Salary'])->id;
    }
}
