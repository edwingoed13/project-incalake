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
            // Remove old gallery_layout column
            $table->dropColumn('gallery_layout');

            // Remove video_first column (ya no se necesita con detección automática)
            $table->dropColumn('video_first');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Restore old columns
            $table->string('gallery_layout', 50)->default('hero_mosaic')->after('youtube_url');
            $table->boolean('video_first')->default(true)->after('youtube_url');
        });
    }
};
