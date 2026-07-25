<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->belongsToFamily($user, $loan);
    }

    public function create(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    /**
     * The loan's owner, or an admin of its family, may edit it and manage its
     * payments.
     */
    public function update(User $user, Loan $loan): bool
    {
        if (! $this->belongsToFamily($user, $loan)) {
            return false;
        }

        return $loan->user_id === $user->id
            || $user->isAdminOf($loan->family);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $this->update($user, $loan);
    }

    protected function belongsToFamily(User $user, Loan $loan): bool
    {
        return $user->familyMemberships()
            ->where('family_id', $loan->family_id)
            ->exists();
    }
}
