<?php

use App\Models\Loan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The amortization schedule for an EMI loan: one row per monthly installment,
 * generated up front by EmiService so the principal/interest split, total
 * payable and remaining tenure are all known in advance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_emis', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Loan::class)->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->decimal('emi_amount', 14, 2);
            $table->decimal('principal_component', 14, 2);
            $table->decimal('interest_component', 14, 2);
            $table->decimal('opening_balance', 14, 2);
            $table->decimal('closing_balance', 14, 2);
            $table->string('status')->default('pending'); // pending | paid
            $table->date('paid_on')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'sequence']);
            $table->index(['loan_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_emis');
    }
};
