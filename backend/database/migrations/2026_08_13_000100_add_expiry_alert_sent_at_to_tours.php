<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers when a tour last had an expiry warning sent, so the recurring
 * check can run as often as it likes without repeating itself.
 *
 * One column covers both cases the alert has to handle:
 *  - a tour approaching its end date is warned again every 15 days;
 *  - a tour already past it is reported once, in the backlog summary, and
 *    then left alone. 133 of the 290 tours are in that state — some expired
 *    in 2019 — so repeating those on every run would bury the warnings that
 *    actually need acting on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tours', 'expiry_alert_sent_at')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->timestamp('expiry_alert_sent_at')->nullable()->after('availability_data');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tours', 'expiry_alert_sent_at')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->dropColumn('expiry_alert_sent_at');
            });
        }
    }
};
