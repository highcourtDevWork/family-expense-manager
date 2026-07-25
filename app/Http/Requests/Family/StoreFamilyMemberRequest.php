<?php

namespace App\Http\Requests\Family;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\FamilyMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', FamilyMember::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::enum(FamilyRole::class)],
            'relationship' => ['required', Rule::enum(Relationship::class)],
        ];
    }
}
