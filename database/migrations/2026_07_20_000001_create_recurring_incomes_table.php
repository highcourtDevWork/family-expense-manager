<?php

use App\Models\Family;
use App\Models\IncomeSource;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates for income that repeats every month (salaries, rent received, ...).
 * Unlike recurring expenses, each template names the family member who earns it
 * (user_id) so the generated income is attributed to the right person.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // The family member who earns this income.
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            // Optional source; a blank template falls back to "Salary".
            $table->foreignIdFor(IncomeSource::class)->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->boolean('auto_post')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_incomes');
    }
};
