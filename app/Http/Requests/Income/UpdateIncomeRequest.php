<?php

namespace App\Http\Requests\Income;

/**
 * Shares its validation rules with StoreIncomeRequest; only the authorization
 * check differs (update the specific income vs. create a new one).
 */
class UpdateIncomeRequest extends StoreIncomeRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('income'));
    }
}
