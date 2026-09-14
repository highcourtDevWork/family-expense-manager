<?php

namespace App\Http\Requests\Bachelor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSplitExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $family = $this->user()?->currentFamily();

        return $family !== null && $family->isBachelor();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $familyId = $this->user()->currentFamily()?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'period' => ['required', 'date'],
            // The payer must be a member of this bachelor account.
            'paid_by' => [
                'required',
                Rule::exists('family_members', 'user_id')->where('family_id', $familyId),
            ],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('family_id', $familyId),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
