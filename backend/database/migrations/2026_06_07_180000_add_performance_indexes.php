<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P1 — Performance indexes.
 *
 * The audit (see SECURITY_P0_HANDOFF.md companion) flagged four tables
 * where high-volume queries hit columns without supporting indexes:
 *
 * 1. languages.code            — joined on every i18n flow (TourController,
 *                                 ReviewController, BookingController). Table
 *                                 had ZERO indexes despite being read on
 *                                 nearly every request.
 * 2. tour_translations.language_id — search + slug lookup. Composite
 *                                 (tour_id, language_id) exists but a single
 *                                 (language_id) is needed for the where
 *                                 clauses that don't pin a tour first.
 * 3. bookings.(payment_status, created_at) — dashboard monthly aggregates
 *                                 + revenue chart. Composite gives both
 *                                 the where filter and the order-by leg.
 * 4. tour_prices.(tour_id, min_quantity, active) — price calc on every
 *                                 booking creation. Extends the existing
 *                                 (tour_id) lookup with the two filters
 *                                 PriceCalculatorService applies inside.
 *
 * Every CREATE INDEX is online (online_alter=DEFAULT in MariaDB 10.4) and
 * the down() drops them all. Safe to run during traffic — index builds on
 * tables of this size (<200k rows) finish in <1s each on cPanel-tier
 * MariaDB.
 *
 * Each index call is guarded by a hasIndex() helper because the deploy
 * runs migrate.php?key=... which is idempotent — re-running this migration
 * (or running it after a partial failure) must not throw on duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. languages.code — adds a btree on the join target. unique would
        //    be cleaner (codes ARE unique in practice) but the column is
        //    nullable in the existing schema; non-unique index is safe and
        //    achieves the same lookup speed.
        if (Schema::hasTable('languages') && !$this->hasIndex('languages', 'languages_code_index')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->index('code', 'languages_code_index');
            });
        }

        // 2. tour_translations.language_id — the (tour_id, language_id)
        //    composite already exists; this single-col covers the inverse
        //    direction queries (find all translations in a language without
        //    pinning a tour first).
        if (Schema::hasTable('tour_translations') && !$this->hasIndex('tour_translations', 'tour_translations_language_id_index')) {
            Schema::table('tour_translations', function (Blueprint $table) {
                $table->index('language_id', 'tour_translations_language_id_index');
            });
        }

        // 3. bookings(payment_status, created_at) — dashboard scans paid
        //    rows in a date range. Composite leftmost column matches the
        //    where filter; created_at on the right covers the ORDER BY.
        if (Schema::hasTable('bookings') && !$this->hasIndex('bookings', 'bookings_payment_status_created_at_index')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index(['payment_status', 'created_at'], 'bookings_payment_status_created_at_index');
            });
        }

        // 4. tour_prices(tour_id, min_quantity, active) — booking creation
        //    looks up the price tier matching the pax count. Existing
        //    (tour_id) lookup already short-circuits to one tour; the
        //    remaining filter cost is min_quantity range + active=1.
        if (Schema::hasTable('tour_prices') && !$this->hasIndex('tour_prices', 'tour_prices_tour_id_min_quantity_active_index')) {
            Schema::table('tour_prices', function (Blueprint $table) {
                $table->index(['tour_id', 'min_quantity', 'active'], 'tour_prices_tour_id_min_quantity_active_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tour_prices') && $this->hasIndex('tour_prices', 'tour_prices_tour_id_min_quantity_active_index')) {
            Schema::table('tour_prices', function (Blueprint $table) {
                $table->dropIndex('tour_prices_tour_id_min_quantity_active_index');
            });
        }
        if (Schema::hasTable('bookings') && $this->hasIndex('bookings', 'bookings_payment_status_created_at_index')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex('bookings_payment_status_created_at_index');
            });
        }
        if (Schema::hasTable('tour_translations') && $this->hasIndex('tour_translations', 'tour_translations_language_id_index')) {
            Schema::table('tour_translations', function (Blueprint $table) {
                $table->dropIndex('tour_translations_language_id_index');
            });
        }
        if (Schema::hasTable('languages') && $this->hasIndex('languages', 'languages_code_index')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->dropIndex('languages_code_index');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, $table, $indexName]
        );
        return !empty($rows);
    }
};
