<?php

namespace App\Http\Requests\Loan;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Managing payments requires permission to update the parent loan.
        return $this->user()->can('update', $this->route('loan'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Loan $loan */
        $loan = $this->route('loan');

        return [
            'payment_date' => ['required', 'date'],
            // A repayment can never exceed what is still owed.
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$loan->remaining_amount],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'The payment cannot be more than the remaining balance.',
        ];
    }
}
