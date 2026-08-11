<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimistic concurrency for tour drafts.
 *
 * There is one revision per tour, so two operators editing the same published
 * tour both autosave into it and the later write silently erases the earlier
 * one — no warning, no trace, and the person who lost their work only finds
 * out when they reload.
 *
 * A counter the client echoes back is enough to catch it: if the stored
 * version moved on since the client last read it, someone else saved in
 * between. A timestamp would do the same job in principle but is fragile
 * across serialization formats and clock resolution; an integer is not.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tour_revisions')) {
            return;
        }

        if (!Schema::hasColumn('tour_revisions', 'version')) {
            Schema::table('tour_revisions', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('schema_version');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tour_revisions', 'version')) {
            Schema::table('tour_revisions', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }
};
