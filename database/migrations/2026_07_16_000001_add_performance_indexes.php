<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the common "this family, filtered by member" and
 * "this family, ordered by date" queries the list screens run. Eager loading
 * already prevents N+1 selects; these help the WHERE/ORDER BY stay index-backed
 * as the tables grow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['family_id', 'user_id'], 'expenses_family_user_index');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->index(['family_id', 'user_id'], 'incomes_family_user_index');
        });

        Schema::table('savings', function (Blueprint $table) {
            $table->index(['family_id', 'date'], 'savings_family_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_family_user_index');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex('incomes_family_user_index');
        });

        Schema::table('savings', function (Blueprint $table) {
            $table->dropIndex('savings_family_date_index');
        });
    }
};
