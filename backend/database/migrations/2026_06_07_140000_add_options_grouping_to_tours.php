<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add parent/option grouping to tours so a single "activity" (e.g. Uros +
 * Taquile clásico) can present its variants — Compartido / +Guía / Privado —
 * as inline options on the detail page instead of as separate listings.
 *
 * - parent_tour_id  : self FK; null on the canonical "parent" tour,
 *                     set on every child variant.
 * - option_label    : short label shown on the option card ("Compartido",
 *                     "+Guía Privado", "Privado", etc.). null on tours
 *                     that have no siblings.
 * - option_color    : tailwind-friendly color token for the badge
 *                     ("blue" | "violet" | "amber" | …). null for default.
 *
 * Children are excluded from the public listing and set to canonical→parent
 * via the existing TourTranslation.canonical_url + index_status='noindex'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->foreignId('parent_tour_id')
                ->nullable()
                ->after('city_id')
                ->constrained('tours')
                ->nullOnDelete()
                ->comment('Parent activity tour; siblings (variants) share this.');
            $table->string('option_label', 50)->nullable()->after('parent_tour_id')
                ->comment('Short variant label (Compartido / +Guía / Privado).');
            $table->string('option_color', 20)->nullable()->after('option_label')
                ->comment('Badge color token (blue/violet/amber).');

            $table->index('parent_tour_id');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropForeign(['parent_tour_id']);
            $table->dropIndex(['parent_tour_id']);
            $table->dropColumn(['parent_tour_id', 'option_label', 'option_color']);
        });
    }
};
