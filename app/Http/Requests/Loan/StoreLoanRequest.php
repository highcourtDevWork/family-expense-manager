<?php

namespace App\Http\Requests\Loan;

use App\Enums\LoanType;
use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Loan::class);
    }

    /**
     * Normalise the EMI toggle to a real boolean and clear EMI fields when it
     * is off, so stale values never persist on a non-EMI loan.
     */
    protected function prepareForValidation(): void
    {
        $isEmi = $this->boolean('is_emi');

        $this->merge(['is_emi' => $isEmi]);

        if (! $isEmi) {
            $this->merge([
                'tenure_months' => null,
                'emi_amount' => null,
                'emi_due_day' => null,
                'interest_rate' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiredForEmi = Rule::requiredIf(fn (): bool => $this->boolean('is_emi'));

        return [
            'loan_name' => ['required', 'string', 'max:255'],
            'loan_type' => ['required', Rule::enum(LoanType::class)],
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'is_emi' => ['boolean'],
            'tenure_months' => [$requiredForEmi, 'nullable', 'integer', 'min:1', 'max:600'],
            'interest_rate' => [$requiredForEmi, 'nullable', 'numeric', 'min:0', 'max:100'],
            'emi_due_day' => [$requiredForEmi, 'nullable', 'integer', 'min:1', 'max:28'],
            // Optional — the EmiService computes it from principal/rate/tenure
            // when left blank.
            'emi_amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
        ];
    }
}
