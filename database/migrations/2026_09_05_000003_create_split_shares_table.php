<?php

use App\Models\Family;
use App\Models\SplitExpense;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One member's portion of a split expense, and whether they've settled it.
 * The payer's own share is created already settled (they fronted the money).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_shares', function (Blueprint $table) {
            $table->id();
            // Denormalised family_id so shares can be scoped without a join.
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SplitExpense::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            // Null until the member settles up with the payer.
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            // A member has exactly one share per split expense.
            $table->unique(['split_expense_id', 'user_id']);
            $table->index(['family_id', 'user_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_shares');
    }
};
