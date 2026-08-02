<?php

namespace App\Http\Requests\Recurring;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringExpenseRequest extends FormRequest
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
            // Optional category, but it must belong to the user's own family.
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('family_id', $familyId),
            ],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'auto_post' => ['boolean'],
            'active' => ['boolean'],
        ];
    }
}
