<?php

namespace App\Models;

use Database\Factories\SplitExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SplitExpense extends Model
{
    /** @use HasFactory<SplitExpenseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'paid_by',
        'created_by',
        'expense_category_id',
        'title',
        'total_amount',
        'period',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'period' => 'date',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * The member who fronted the money.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * @return HasMany<SplitShare>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(SplitShare::class);
    }

    /**
     * Amount still owed to the payer across all members (settled shares excluded).
     */
    public function outstandingAmount(): string
    {
        return (string) $this->shares()->whereNull('settled_at')->sum('amount');
    }
}
