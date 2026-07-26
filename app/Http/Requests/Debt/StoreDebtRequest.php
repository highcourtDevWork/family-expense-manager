<?php

namespace App\Http\Requests\Debt;

use App\Enums\DebtType;
use App\Models\Debt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Debt::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'person_name' => ['required', 'string', 'max:255'],
            'debt_type' => ['required', Rule::enum(DebtType::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
