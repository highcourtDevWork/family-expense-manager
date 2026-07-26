<?php

namespace App\Http\Requests\Saving;

use App\Enums\SavingType;
use App\Models\Saving;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Saving::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'saving_type' => ['required', Rule::enum(SavingType::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
