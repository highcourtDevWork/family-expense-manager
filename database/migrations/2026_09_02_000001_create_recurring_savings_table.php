<?php

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates for savings that repeat every month (SIPs, recurring deposits,
 * gold plans, ...). Like recurring income, each template names the family
 * member it is attributed to (user_id) and carries a saving_type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_savings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            // The family member this saving is attributed to.
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('saving_type')->default('bank');
            $table->decimal('amount', 12, 2);
            $table->boolean('auto_post')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_savings');
    }
};
