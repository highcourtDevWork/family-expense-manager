<?php

namespace App\Http\Requests\Family;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only the membership itself (role, relationship) is editable here. Changing a
 * member's name/email/password is account management, not family management,
 * and deliberately stays out of an admin's reach.
 */
class UpdateFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('family_member'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(FamilyRole::class)],
            'relationship' => ['required', Rule::enum(Relationship::class)],
        ];
    }
}
