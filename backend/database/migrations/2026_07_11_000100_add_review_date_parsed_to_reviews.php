<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews were ordered by created_at (the IMPORT timestamp) while the real
 * review date lives in `review_date` — a free-form string ("jul. 2024",
 * "July 2024", "12 de julio de 2024") that can't be sorted. Add a sortable
 * date column and backfill it by parsing the known formats; the controller
 * then orders by COALESCE(review_date_parsed, created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->date('review_date_parsed')->nullable()->after('review_date')->index();
        });

        // Backfill: parse "month year" / "day month year" strings, es + en.
        $months = [
            'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'ago' => 8, 'sep' => 9, 'set' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
            'jan' => 1, 'apr' => 4, 'aug' => 8, 'dec' => 12,
        ];

        DB::table('reviews')->whereNotNull('review_date')->orderBy('id')->chunkById(200, function ($rows) use ($months) {
            foreach ($rows as $row) {
                $raw = mb_strtolower(trim((string) $row->review_date));
                if ($raw === '') {
                    continue;
                }

                $year = null;
                if (preg_match('/(19|20)\d{2}/', $raw, $m)) {
                    $year = (int) $m[0];
                }
                if (!$year) {
                    continue;
                }

                $month = null;
                foreach ($months as $token => $num) {
                    if (str_contains($raw, $token)) {
                        $month = $num;
                        break;
                    }
                }
                // Numeric formats like "2024-07" / "07/2024"
                if (!$month && preg_match('/\b(0?[1-9]|1[0-2])[\/\-]/', $raw, $m)) {
                    $month = (int) $m[1];
                }
                $month = $month ?: 1;

                $day = 1;
                if (preg_match('/\b([0-3]?\d)\b(?!\d)/', $raw, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 31 && (int) $m[1] !== $year % 100) {
                    $day = (int) $m[1];
                }

                if (checkdate($month, $day, $year)) {
                    DB::table('reviews')->where('id', $row->id)->update([
                        'review_date_parsed' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('review_date_parsed');
        });
    }
};
