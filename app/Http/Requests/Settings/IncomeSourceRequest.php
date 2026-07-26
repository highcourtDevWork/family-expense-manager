<?php

namespace App\Http\Requests\Settings;

use App\Models\IncomeSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncomeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentFamily() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $familyId = $this->user()->currentFamily()?->id;

        /** @var IncomeSource|null $source */
        $source = $this->route('source');

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('income_sources', 'name')
                    ->where('family_id', $familyId)
                    ->ignore($source?->id),
            ],
        ];
    }
}
