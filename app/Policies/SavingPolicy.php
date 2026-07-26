<?php

namespace App\Policies;

use App\Models\Saving;
use App\Models\User;

class SavingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    public function create(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    public function update(User $user, Saving $saving): bool
    {
        if (! $this->belongsToFamily($user, $saving)) {
            return false;
        }

        return $saving->user_id === $user->id
            || $user->isAdminOf($saving->family);
    }

    public function delete(User $user, Saving $saving): bool
    {
        return $this->update($user, $saving);
    }

    protected function belongsToFamily(User $user, Saving $saving): bool
    {
        return $user->familyMemberships()
            ->where('family_id', $saving->family_id)
            ->exists();
    }
}
