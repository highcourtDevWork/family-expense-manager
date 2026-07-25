<?php

use App\Models\ExpenseCategory;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // Who spent the money. Keep the row if the user is deleted so the
            // family's historical totals stay accurate.
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(ExpenseCategory::class)->constrained()->restrictOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash');
            $table->string('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index for the most common query: "this family's expenses,
            // newest first" and date-range filters.
            $table->index(['family_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
