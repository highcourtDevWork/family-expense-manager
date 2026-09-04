<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "reference" idempotency key so a saving created from a recurring template
 * can only ever be posted once per month (mirrors incomes.reference).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('notes')->index();
        });
    }

    public function down(): void
    {
        Schema::table('savings', function (Blueprint $table) {
            $table->dropIndex(['reference']);
            $table->dropColumn('reference');
        });
    }
};
