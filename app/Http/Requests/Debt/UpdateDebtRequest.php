<?php

namespace App\Http\Requests\Debt;

class UpdateDebtRequest extends StoreDebtRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('debt'));
    }
}
