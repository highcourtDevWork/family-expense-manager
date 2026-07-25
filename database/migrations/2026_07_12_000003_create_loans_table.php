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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // The member who holds the loan.
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->string('loan_name');
            $table->string('loan_type')->default('other');
            $table->decimal('total_amount', 14, 2);
            // paid_amount and remaining_amount are derived from loan_payments and
            // kept in sync by LoanService::recalculate() after every change, so
            // the dashboard can SUM(remaining_amount) cheaply.
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
