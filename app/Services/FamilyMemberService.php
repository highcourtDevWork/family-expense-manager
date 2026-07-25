<?php

namespace App\Services;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class FamilyMemberService
{
    /**
     * Create a login account for a new family member and attach them to the
     * family, atomically — we never want a User with no membership.
     *
     * @param  array<string, mixed>  $data
     */
    public function addMember(Family $family, array $data): FamilyMember
    {
        return DB::transaction(function () use ($family, $data): FamilyMember {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return $family->members()->create([
                'user_id' => $user->id,
                'role' => $data['role'],
                'relationship' => $data['relationship'],
            ]);
        });
    }

    /**
     * Update a member's role/relationship.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function updateMember(FamilyMember $member, array $data): FamilyMember
    {
        $this->guardCreatorStaysAdmin($member, FamilyRole::from($data['role']));

        $member->update([
            'role' => $data['role'],
            'relationship' => $data['relationship'],
        ]);

        return $member;
    }

    /**
     * Remove a member from the family. Their expenses are left intact so the
     * family's historical totals stay correct.
     *
     * @throws ValidationException
     */
    public function removeMember(FamilyMember $member, User $actor): void
    {
        if ($member->user_id === $member->family->created_by) {
            throw ValidationException::withMessages([
                'member' => 'The family creator cannot be removed.',
            ]);
        }

        if ($member->user_id === $actor->id) {
            throw ValidationException::withMessages([
                'member' => 'You cannot remove yourself from the family.',
            ]);
        }

        $member->delete();
    }

    /**
     * The family creator must always remain an admin — this is what guarantees
     * a family can never be left with nobody able to administer it.
     *
     * @throws ValidationException
     */
    private function guardCreatorStaysAdmin(FamilyMember $member, FamilyRole $newRole): void
    {
        $isCreator = $member->user_id === $member->family->created_by;

        if ($isCreator && $newRole !== FamilyRole::Admin) {
            throw ValidationException::withMessages([
                'role' => 'The family creator must remain an admin.',
            ]);
        }
    }
}
