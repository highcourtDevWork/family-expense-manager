<?php

use App\Models\ExpenseCategory;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates for expenses that repeat every month (rent, internet, ...). They
 * are not expenses themselves — one click (or the monthly scheduler) creates an
 * actual expense row from a template.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            // Category is optional; a blank template falls back to "Others".
            $table->foreignIdFor(ExpenseCategory::class)->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash');
            // When true the monthly scheduler posts it automatically.
            $table->boolean('auto_post')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
