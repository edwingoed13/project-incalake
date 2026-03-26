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
        \DB::statement("ALTER TABLE `tours` MODIFY COLUMN `gallery_layout` ENUM('hero_mosaic', 'full_width_hero', 'video_image', 'masonry_grid', 'carousel') DEFAULT 'hero_mosaic'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("ALTER TABLE `tours` MODIFY COLUMN `gallery_layout` ENUM('hero_mosaic', 'full_width_hero', 'video_image', 'masonry_grid') DEFAULT 'hero_mosaic'");
    }
};
