<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Non-destructive cropping: keep the full (optimized) original alongside the
     * displayed crop, plus the crop box so the editor can restore it exactly.
     * Idempotent so prod migrate.php can re-run safely.
     */
    public function up(): void
    {
        Schema::table('tour_media_gallery', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_media_gallery', 'original_path')) {
                // Full image the crop was derived from (kept for re-editing).
                $table->string('original_path')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('tour_media_gallery', 'crop_data')) {
                // { coordinates: {left,top,width,height}, aspect: number|null }
                $table->json('crop_data')->nullable()->after('original_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tour_media_gallery', function (Blueprint $table) {
            foreach (['original_path', 'crop_data'] as $col) {
                if (Schema::hasColumn('tour_media_gallery', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
