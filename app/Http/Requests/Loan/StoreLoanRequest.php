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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'loan_name' => ['required', 'string', 'max:255'],
            'loan_type' => ['required', Rule::enum(LoanType::class)],
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
