<?php

namespace App\Models;

use App\Enums\SavingType;
use Database\Factories\RecurringSavingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class RecurringSaving extends Model
{
    /** @use HasFactory<RecurringSavingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'family_id',
        'user_id',
        'name',
        'saving_type',
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
            'saving_type' => SavingType::class,
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
     * The family member this saving is attributed to.
     */
    public function saver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The idempotency key a saving created from this template for the given
     * month carries, so it can only ever be posted once per month.
     */
    public function referenceFor(Carbon $month): string
    {
        return 'recurring-saving:'.$this->id.':'.$month->format('Y-m');
    }
}
