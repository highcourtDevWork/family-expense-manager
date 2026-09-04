<?php

namespace App\Models;

use App\Enums\SavingType;
use Database\Factories\SavingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Saving extends Model
{
    /** @use HasFactory<SavingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'saving_type',
        'amount',
        'notes',
        'date',
        'reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saving_type' => SavingType::class,
            'amount' => 'decimal:2',
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

    /**
     * @param  Builder<Saving>  $query
     * @return Builder<Saving>
     */
    public function scopeForFamily(Builder $query, int $familyId): Builder
    {
        return $query->where('family_id', $familyId);
    }
}
