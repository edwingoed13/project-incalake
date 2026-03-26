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
        Schema::table('tours', function (Blueprint $table) {
            $table->enum('gallery_layout', ['hero_mosaic', 'full_width_hero', 'video_image', 'masonry_grid'])
                  ->default('hero_mosaic')
                  ->after('youtube_url');
            $table->boolean('video_first')->default(true)->after('gallery_layout');
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
