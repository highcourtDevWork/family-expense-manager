<?php

namespace App\Http\Requests\Expense;

/**
 * Shares its validation rules with StoreExpenseRequest; only the authorization
 * check differs (update the specific expense vs. create a new one).
 */
class UpdateExpenseRequest extends StoreExpenseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('expense'));
    }
}
