<?php

namespace App\Http\Requests\Settings;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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

        /** @var ExpenseCategory|null $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:255',
                // Names are unique within the family; ignore self when updating.
                Rule::unique('expense_categories', 'name')
                    ->where('family_id', $familyId)
                    ->ignore($category?->id),
            ],
        ];
    }
}
