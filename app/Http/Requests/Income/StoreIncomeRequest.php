<?php

namespace App\Http\Requests\Income;

use App\Models\Income;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Income::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $family = $this->user()->currentFamily();

        return [
            // The source must belong to the user's own family, so a crafted
            // request can't attach income to another family's source.
            'income_source_id' => [
                'required',
                Rule::exists('income_sources', 'id')
                    ->where('family_id', $family?->id),
            ],
            'income_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
