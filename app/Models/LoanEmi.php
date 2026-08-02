<?php

namespace App\Models;

use App\Enums\EmiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanEmi extends Model
{
    protected $table = 'loan_emis';

    protected $fillable = [
        'loan_id',
        'sequence',
        'due_date',
        'emi_amount',
        'principal_component',
        'interest_component',
        'opening_balance',
        'closing_balance',
        'status',
        'paid_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_on' => 'date',
            'status' => EmiStatus::class,
            'emi_amount' => 'decimal:2',
            'principal_component' => 'decimal:2',
            'interest_component' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
