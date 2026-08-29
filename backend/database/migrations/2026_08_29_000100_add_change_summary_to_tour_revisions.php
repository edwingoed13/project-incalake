<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a parked draft actually changes, so the tour LIST can say it.
 *
 * `payload` is one opaque snapshot of the whole wizard, so "cambios sin
 * publicar" could only ever be a yes/no: an operator looking at a tour with six
 * languages had no way to tell which of them was waiting, short of opening the
 * editor and comparing by eye.
 *
 * The difference is only computable in the wizard, which is the one place that
 * holds the live tour and the draft in the SAME shape — the DB stores
 * API-shaped rows and the payload is wizard-shaped, and re-deriving one from
 * the other in PHP would mean a second copy of the mapping to keep in sync.
 * So the client computes it and we persist the answer here.
 *
 * Nullable on purpose: drafts parked before this existed have no summary, and
 * a missing summary must read as "unknown" (say nothing) rather than "no
 * changes", which would be a confident lie.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tour_revisions')) {
            return;
        }

        Schema::table('tour_revisions', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_revisions', 'changed_languages')) {
                // Uppercase language codes, e.g. ["EN","ES"].
                $table->json('changed_languages')->nullable()->after('schema_version');
            }
            if (!Schema::hasColumn('tour_revisions', 'changed_sections')) {
                // Human-readable non-language sections, e.g. ["Precios"].
                $table->json('changed_sections')->nullable()->after('changed_languages');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tour_revisions')) {
            return;
        }

        Schema::table('tour_revisions', function (Blueprint $table) {
            foreach (['changed_languages', 'changed_sections'] as $column) {
                if (Schema::hasColumn('tour_revisions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
