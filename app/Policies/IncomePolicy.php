<?php

namespace App\Policies;

use App\Models\Income;
use App\Models\User;

class IncomePolicy
{
    /**
     * Any authenticated user with a family may view their family's income list.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    public function view(User $user, Income $income): bool
    {
        return $this->belongsToFamily($user, $income);
    }

    /**
     * Any family member may record income.
     */
    public function create(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    /**
     * The owner of the income entry, or an admin of its family, may edit it.
     */
    public function update(User $user, Income $income): bool
    {
        if (! $this->belongsToFamily($user, $income)) {
            return false;
        }

        return $income->user_id === $user->id
            || $user->isAdminOf($income->family);
    }

    public function delete(User $user, Income $income): bool
    {
        return $this->update($user, $income);
    }

    protected function belongsToFamily(User $user, Income $income): bool
    {
        return $user->familyMemberships()
            ->where('family_id', $income->family_id)
            ->exists();
    }
}
