<?php

namespace App\Http\Requests\Recurring;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringIncomeRequest extends FormRequest
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
            // The earner must be a member of the user's own family.
            'user_id' => [
                'required',
                Rule::exists('family_members', 'user_id')->where('family_id', $familyId),
            ],
            // Optional source, scoped to the family when given.
            'income_source_id' => [
                'nullable',
                Rule::exists('income_sources', 'id')->where('family_id', $familyId),
            ],
            'auto_post' => ['boolean'],
            'active' => ['boolean'],
        ];
    }
}
