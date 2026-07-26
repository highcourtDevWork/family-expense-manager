<?php

namespace App\Http\Requests\Import;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class ImportStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Importing writes expenses/income, so require permission to create them.
        return $this->user()->can('create', Expense::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'statement' => ['required', 'file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statement.required' => 'Please choose a Google Pay statement PDF to import.',
            'statement.mimetypes' => 'The statement must be a PDF file.',
            'statement.mimes' => 'The statement must be a PDF file.',
        ];
    }
}
