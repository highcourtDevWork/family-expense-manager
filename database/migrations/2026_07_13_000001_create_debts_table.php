<?php

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // The member who owns this debt record.
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->string('person_name');
            $table->string('debt_type')->default('borrowed');
            $table->decimal('amount', 14, 2);
            // paid_amount and balance_amount are derived from debt_payments and
            // kept in sync by DebtService::recalculate() after every change.
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_amount', 14, 2)->default(0);
            $table->date('date');
            $table->string('notes')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'debt_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
