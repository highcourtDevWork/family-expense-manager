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
        Schema::create('savings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
            $table->string('saving_type')->default('bank');
            $table->decimal('amount', 14, 2);
            $table->string('notes')->nullable();
            $table->date('date');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'saving_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings');
    }
};
