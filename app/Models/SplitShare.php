<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitShare extends Model
{
    protected $fillable = [
        'family_id',
        'split_expense_id',
        'user_id',
        'amount',
        'settled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function splitExpense(): BelongsTo
    {
        return $this->belongsTo(SplitExpense::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    /**
     * @param  Builder<SplitShare>  $query
     * @return Builder<SplitShare>
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereNull('settled_at');
    }
}
