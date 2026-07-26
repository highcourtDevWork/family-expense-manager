<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A nullable `reference` holds the source transaction id (e.g. a UPI
 * Transaction ID) for rows created by the statement importer. It lets a
 * re-uploaded statement be de-duplicated instead of importing twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('description');
            $table->index(['family_id', 'reference']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('notes');
            $table->index(['family_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['family_id', 'reference']);
            $table->dropColumn('reference');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['family_id', 'reference']);
            $table->dropColumn('reference');
        });
    }
};
