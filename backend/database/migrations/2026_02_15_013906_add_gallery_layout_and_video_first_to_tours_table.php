<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // video_first is also added by 2026_02_14_000213_add_media_layout_to_tours_table.
        // Guarded so a from-scratch migrate doesn't die on a duplicate column.
        Schema::table('tours', function (Blueprint $table) {
            if (!Schema::hasColumn('tours', 'gallery_layout')) {
                $table->enum('gallery_layout', ['hero_mosaic', 'full_width_hero', 'video_image', 'masonry_grid'])
                      ->default('hero_mosaic')
                      ->after('youtube_url');
            }
            if (!Schema::hasColumn('tours', 'video_first')) {
                $table->boolean('video_first')->default(true)->after('gallery_layout');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['gallery_layout', 'video_first']);
        });
    }
};
