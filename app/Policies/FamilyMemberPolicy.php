<?php

namespace App\Policies;

use App\Models\FamilyMember;
use App\Models\User;

class FamilyMemberPolicy
{
    /**
     * Any member of a family may see who else is in it.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentFamily() !== null;
    }

    /**
     * Only a family admin may add new members.
     */
    public function create(User $user): bool
    {
        $family = $user->currentFamily();

        return $family !== null && $user->isAdminOf($family);
    }

    /**
     * Only an admin of *that member's* family may change their role or
     * relationship.
     */
    public function update(User $user, FamilyMember $member): bool
    {
        return $user->isAdminOf($member->family);
    }

    /**
     * Same rule as update: admin of the member's own family.
     */
    public function delete(User $user, FamilyMember $member): bool
    {
        return $user->isAdminOf($member->family);
    }
}
