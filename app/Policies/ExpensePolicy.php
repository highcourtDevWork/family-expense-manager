<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Any authenticated user may view their family's expense list. The list
     * itself is always scoped to the current family in the controller.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    /**
     * A user may view an expense only if they belong to its family.
     */
    public function view(User $user, Expense $expense): bool
    {
        return $this->belongsToFamily($user, $expense);
    }

    /**
     * Any family member may record a new expense.
     */
    public function create(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    /**
     * The owner of the expense, or an admin of its family, may edit it.
     */
    public function update(User $user, Expense $expense): bool
    {
        if (! $this->belongsToFamily($user, $expense)) {
            return false;
        }

        return $expense->user_id === $user->id
            || $user->isAdminOf($expense->family);
    }

    /**
     * Same rule as update: owner or family admin.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }

    /**
     * Whether the user is a member of the expense's family.
     */
    protected function belongsToFamily(User $user, Expense $expense): bool
    {
        return $user->familyMemberships()
            ->where('family_id', $expense->family_id)
            ->exists();
    }
}
