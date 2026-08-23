<?php

namespace App\Models;

use Database\Factories\RecurringIncomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class RecurringIncome extends Model
{
    /** @use HasFactory<RecurringIncomeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'income_source_id',
        'name',
        'amount',
        'auto_post',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'auto_post' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * The family member this income is attributed to.
     */
    public function earner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(IncomeSource::class, 'income_source_id');
    }

    /**
     * The idempotency key an income created from this template for the given
     * month carries, so it can only ever be posted once per month.
     */
    public function referenceFor(Carbon $month): string
    {
        return 'recurring-income:'.$this->id.':'.$month->format('Y-m');
    }
}
