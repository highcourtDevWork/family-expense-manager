<?php

namespace App\Http\Requests\Saving;

class UpdateSavingRequest extends StoreSavingRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('saving'));
    }
}
