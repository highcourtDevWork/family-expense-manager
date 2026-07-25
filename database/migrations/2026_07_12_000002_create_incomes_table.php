<?php

use App\Models\Family;
use App\Models\IncomeSource;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // Who earned it. Keep the row if the user is deleted so the family's
            // historical income totals stay accurate.
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(IncomeSource::class)->constrained()->restrictOnDelete();
            $table->date('income_date');
            $table->decimal('amount', 12, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Matches the common query: "this family's income, by date".
            $table->index(['family_id', 'income_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
