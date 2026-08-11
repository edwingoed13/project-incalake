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
        // Guarded: the columns this drops are added by two earlier migrations
        // that overlap, so on a fresh DB one of them may never have been
        // created and dropColumn would fail on a missing column.
        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'gallery_layout')) {
                $table->dropColumn('gallery_layout');
            }

            // (ya no se necesita con detección automática)
            if (Schema::hasColumn('tours', 'video_first')) {
                $table->dropColumn('video_first');
            }
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
