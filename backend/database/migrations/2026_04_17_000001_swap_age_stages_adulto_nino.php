<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The original seeder created Niño (id=1) and Adulto (id=2), but the admin
 * wizard always treated slot '1' as Adulto. Every price saved through the
 * wizard landed under age_stage_id=1 (labeled Niño in DB) and vice versa.
 *
 * Swap the AgeStage rows so id=1 becomes Adulto and id=2 becomes Niño. The
 * tour_prices table keeps its foreign keys intact — the labels now match the
 * data users actually entered.
 *
 * Only runs when the current state matches the broken seeder (row1=Niño,
 * row2=Adulto). Otherwise it's a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row1 = DB::table('age_stages')->where('id', 1)->first();
        $row2 = DB::table('age_stages')->where('id', 2)->first();

        if (!$row1 || !$row2) {
            return;
        }

        $isInverted = strcasecmp(trim($row1->description ?? ''), 'Niño') === 0
            && strcasecmp(trim($row2->description ?? ''), 'Adulto') === 0;

        if (!$isInverted) {
            return;
        }

        DB::transaction(function () {
            DB::table('age_stages')->where('id', 1)->update([
                'description' => 'Adulto',
                'min_age' => 16,
                'max_age' => 99,
                'updated_at' => now(),
            ]);

            DB::table('age_stages')->where('id', 2)->update([
                'description' => 'Niño',
                'min_age' => 3,
                'max_age' => 11,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        $row1 = DB::table('age_stages')->where('id', 1)->first();
        $row2 = DB::table('age_stages')->where('id', 2)->first();

        if (!$row1 || !$row2) {
            return;
        }

        $isSwapped = strcasecmp(trim($row1->description ?? ''), 'Adulto') === 0
            && strcasecmp(trim($row2->description ?? ''), 'Niño') === 0;

        if (!$isSwapped) {
            return;
        }

        DB::transaction(function () {
            DB::table('age_stages')->where('id', 1)->update([
                'description' => 'Niño',
                'min_age' => 0,
                'max_age' => 3,
                'updated_at' => now(),
            ]);

            DB::table('age_stages')->where('id', 2)->update([
                'description' => 'Adulto',
                'min_age' => 18,
                'max_age' => 99,
                'updated_at' => now(),
            ]);
        });
    }
};
