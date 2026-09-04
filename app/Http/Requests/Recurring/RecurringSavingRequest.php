<?php

namespace App\Http\Requests\Recurring;

use App\Enums\SavingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringSavingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentFamily() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_post' => $this->boolean('auto_post'),
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $familyId = $this->user()->currentFamily()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'saving_type' => ['required', Rule::enum(SavingType::class)],
            // The member must belong to the user's own family.
            'user_id' => [
                'required',
                Rule::exists('family_members', 'user_id')->where('family_id', $familyId),
            ],
            'auto_post' => ['boolean'],
            'active' => ['boolean'],
        ];
    }
}
