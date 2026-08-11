<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Draft buffer for published tours.
 *
 * Autosave ships the tour's CURRENT status, so editing a published tour wrote
 * straight through to the live row and half-finished edits reached the public
 * site ~2s after typing, with no confirmation step. A revision parks those
 * edits until the operator explicitly publishes them.
 *
 * `payload` holds the admin wizard's own state slices (basicInfo, contentSEO,
 * ...), NOT the API payload: publishing then means loading the slices back into
 * the wizard and letting the existing save path build + send the payload, so
 * there is no second "apply" implementation to keep in sync with TourService.
 * `schema_version` lets us drop drafts written by an older wizard shape instead
 * of restoring them into fields that no longer exist.
 *
 * One row per tour — the wizard saves full-tour state every time, so the newest
 * payload supersedes the previous one entirely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tour_revisions')) {
            return;
        }

        Schema::create('tour_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->json('payload');
            $table->string('schema_version', 20)->default('v1');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('tour_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_revisions');
    }
};
