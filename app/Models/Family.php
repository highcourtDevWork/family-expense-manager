<?php

namespace App\Models;

use App\Enums\AccountType;
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
        'account_type',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
        ];
    }

    public function isFamily(): bool
    {
        return $this->account_type === AccountType::Family;
    }

    public function isBachelor(): bool
    {
        return $this->account_type === AccountType::Bachelor;
    }

    public function isIndividual(): bool
    {
        return $this->account_type === AccountType::Individual;
    }

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

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function savings(): HasMany
    {
        return $this->hasMany(Saving::class);
    }

    public function recurringExpenses(): HasMany
    {
        return $this->hasMany(RecurringExpense::class);
    }

    public function recurringIncomes(): HasMany
    {
        return $this->hasMany(RecurringIncome::class);
    }

    public function recurringSavings(): HasMany
    {
        return $this->hasMany(RecurringSaving::class);
    }

    public function splitExpenses(): HasMany
    {
        return $this->hasMany(SplitExpense::class);
    }

    public function splitShares(): HasMany
    {
        return $this->hasMany(SplitShare::class);
    }
}
