<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EMI configuration for loans. When is_emi is true the loan is repaid on a
 * fixed monthly schedule (see the loan_emis table) instead of ad-hoc payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->boolean('is_emi')->default(false)->after('loan_type');
            $table->unsignedSmallInteger('tenure_months')->nullable()->after('is_emi');
            $table->decimal('emi_amount', 14, 2)->nullable()->after('tenure_months');
            $table->unsignedTinyInteger('emi_due_day')->nullable()->after('emi_amount');
            // Annual interest rate as a percentage, e.g. 8.50.
            $table->decimal('interest_rate', 5, 2)->nullable()->after('emi_due_day');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['is_emi', 'tenure_months', 'emi_amount', 'emi_due_day', 'interest_rate']);
        });
    }
};
