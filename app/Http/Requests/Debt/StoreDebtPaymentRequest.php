<?php

namespace App\Http\Requests\Debt;

use App\Models\Debt;
use Illuminate\Foundation\Http\FormRequest;

class StoreDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('debt'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Debt $debt */
        $debt = $this->route('debt');

        return [
            'payment_date' => ['required', 'date'],
            // A payment can never exceed what is still outstanding.
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$debt->balance_amount],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'The payment cannot be more than the outstanding balance.',
        ];
    }
}
