<?php

namespace App\Models;

use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'created_by',
    ];

    /**
     * The user who created and owns this family.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The membership pivot rows (role, relationship) for this family.
     */
    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * The users belonging to this family, with their role/relationship pivot.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_members')
            ->withPivot(['role', 'relationship'])
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomeSources(): HasMany
    {
        return $this->hasMany(IncomeSource::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
