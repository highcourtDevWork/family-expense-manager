<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The `relationship` column is now backed by the App\Enums\Relationship enum,
 * which uses lowercase values. Rows written before the enum existed hold
 * capitalised free-text ("Self", "Father", ...) and would throw on read, so
 * translate them to their enum equivalents.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The old creator marker "Self" is now the "head" case.
        DB::table('family_members')
            ->whereRaw('LOWER(relationship) = ?', ['self'])
            ->update(['relationship' => 'head']);

        // Everything else simply lowercases onto its matching case.
        foreach (['Spouse', 'Father', 'Mother', 'Son', 'Daughter', 'Brother', 'Sister', 'Other'] as $value) {
            DB::table('family_members')
                ->where('relationship', $value)
                ->update(['relationship' => strtolower($value)]);
        }

        // Anything left that is not a recognised case falls back to "other".
        DB::table('family_members')
            ->whereNotNull('relationship')
            ->whereNotIn('relationship', [
                'head', 'spouse', 'father', 'mother', 'son', 'daughter', 'brother', 'sister', 'other',
            ])
            ->update(['relationship' => 'other']);
    }

    public function down(): void
    {
        // Purely a data normalisation; there is nothing meaningful to reverse.
    }
};
