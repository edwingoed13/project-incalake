<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tour_media_gallery never had the is_primary column in prod — the model's
 * fillable silently dropped it for years, and adding it to fillable made
 * every insert fail (42S22). Create the column for real.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tour_media_gallery', 'is_primary')) {
            Schema::table('tour_media_gallery', function (Blueprint $table) {
                $table->boolean('is_primary')->default(false)->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tour_media_gallery', 'is_primary')) {
            Schema::table('tour_media_gallery', function (Blueprint $table) {
                $table->dropColumn('is_primary');
            });
        }
    }
};
