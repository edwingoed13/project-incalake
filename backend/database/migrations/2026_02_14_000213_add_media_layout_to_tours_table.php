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
            // Control de orden de multimedia: mostrar video primero si existe
            $table->boolean('video_first')->default(true)->after('youtube_url')->comment('Mostrar video antes que imágenes en el frontend');

            // Layout de galería de imágenes
            $table->enum('gallery_layout', ['hero_mosaic', 'grid', 'carousel'])->default('hero_mosaic')->after('video_first')->comment('Layout de galería: hero_mosaic=1 grande+4 pequeñas, grid=cuadrícula uniforme, carousel=carrusel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['video_first', 'gallery_layout']);
        });
    }
};
