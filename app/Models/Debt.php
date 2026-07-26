<?php

namespace App\Models;

use App\Enums\DebtStatus;
use App\Enums\DebtType;
use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'person_name',
        'debt_type',
        'amount',
        'paid_amount',
        'balance_amount',
        'date',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debt_type' => DebtType::class,
            'status' => DebtStatus::class,
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * How much of the debt has been settled, 0–100, for progress bars.
     */
    public function progressPercentage(): float
    {
        $total = (float) $this->amount;

        if ($total <= 0) {
            return 0.0;
        }

        return min(100, round(((float) $this->paid_amount / $total) * 100, 1));
    }

    /**
     * @param  Builder<Debt>  $query
     * @return Builder<Debt>
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }
}
