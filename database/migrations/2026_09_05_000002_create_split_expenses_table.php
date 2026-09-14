<?php

use App\Models\ExpenseCategory;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A shared household cost for a Bachelor Split account: one member pays the
 * whole amount, and it is divided among the members. The per-member portions
 * live in split_shares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // Who fronted the money.
            $table->foreignIdFor(User::class, 'paid_by')->constrained('users')->restrictOnDelete();
            // Who recorded it (may differ from the payer).
            $table->foreignIdFor(User::class, 'created_by')->constrained('users')->restrictOnDelete();
            $table->foreignIdFor(ExpenseCategory::class)->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->decimal('total_amount', 14, 2);
            // Every split is anchored to a month (stored as its 1st).
            $table->date('period');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_expenses');
    }
};
