<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;

class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    public function view(User $user, Debt $debt): bool
    {
        return $this->belongsToFamily($user, $debt);
    }

    public function create(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    /**
     * The debt's owner, or an admin of its family, may edit it and manage its
     * payments.
     */
    public function update(User $user, Debt $debt): bool
    {
        if (! $this->belongsToFamily($user, $debt)) {
            return false;
        }

        return $debt->user_id === $user->id
            || $user->isAdminOf($debt->family);
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $this->update($user, $debt);
    }

    protected function belongsToFamily(User $user, Debt $debt): bool
    {
        return $user->familyMemberships()
            ->where('family_id', $debt->family_id)
            ->exists();
    }
}
