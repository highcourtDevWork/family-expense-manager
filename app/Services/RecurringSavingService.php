<?php

namespace App\Services;

use App\Models\RecurringSaving;
use App\Models\Saving;
use Illuminate\Support\Carbon;

class RecurringSavingService
{
    /**
     * Post a template as a real saving for the given month, dated the 1st.
     * Idempotent: the saving carries a "recurring-saving:{id}:{YYYY-MM}"
     * reference, so calling this again for the same month returns null instead
     * of duplicating.
     */
    public function postForMonth(RecurringSaving $template, ?Carbon $month = null): ?Saving
    {
        $month ??= Carbon::now();
        $reference = $template->referenceFor($month);

        $alreadyPosted = Saving::query()
            ->where('family_id', $template->family_id)
            ->where('reference', $reference)
            ->exists();

        if ($alreadyPosted) {
            return null;
        }

        return $template->family->savings()->create([
            // Attribute the saving to the template's member, not whoever posts it.
            'user_id' => $template->user_id,
            'saving_type' => $template->saving_type,
            'date' => $month->copy()->startOfMonth(),
            'amount' => $template->amount,
            'notes' => $template->name,
            'reference' => $reference,
        ]);
    }

    /**
     * Post every active, auto-post template for the given month. Used by the
     * monthly scheduler. Returns how many new savings were created.
     */
    public function postAllDue(?Carbon $month = null): int
    {
        $month ??= Carbon::now();
        $created = 0;

        RecurringSaving::query()
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
}
