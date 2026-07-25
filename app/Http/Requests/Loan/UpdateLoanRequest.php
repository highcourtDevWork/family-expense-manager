<?php

namespace App\Http\Requests\Loan;

/**
 * Shares its rules with StoreLoanRequest; only authorization differs.
 */
class UpdateLoanRequest extends StoreLoanRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('loan'));
    }
}
