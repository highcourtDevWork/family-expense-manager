<?php

namespace App\Http\Resources;

use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Income
 */
class IncomeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->income_date->toDateString(),
            'amount' => (float) $this->amount,
            'source' => [
                'id' => $this->income_source_id,
                'name' => $this->whenLoaded('source', fn () => $this->source?->name),
            ],
            'member' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn () => $this->user?->name),
            ],
            'notes' => $this->notes,
            'reference' => $this->reference,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
