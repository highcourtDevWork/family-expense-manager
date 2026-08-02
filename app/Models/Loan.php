<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'loan_name',
        'loan_type',
        'is_emi',
        'tenure_months',
        'emi_amount',
        'emi_due_day',
        'interest_rate',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_type' => LoanType::class,
            'status' => LoanStatus::class,
            'is_emi' => 'boolean',
            'tenure_months' => 'integer',
            'emi_due_day' => 'integer',
            'emi_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
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
        return $this->hasMany(LoanPayment::class);
    }

    /**
     * The amortization schedule (EMI installments), earliest first.
     */
    public function emis(): HasMany
    {
        return $this->hasMany(LoanEmi::class)->orderBy('sequence');
    }

    /**
     * How much of the loan has been repaid, 0–100, for progress bars.
     */
    public function progressPercentage(): float
    {
        $total = (float) $this->total_amount;

        if ($total <= 0) {
            return 0.0;
        }

        return min(100, round(((float) $this->paid_amount / $total) * 100, 1));
    }

    /**
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }
}
