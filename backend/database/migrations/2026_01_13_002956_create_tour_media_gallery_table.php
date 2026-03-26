<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_media_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->string('image_path')->comment('WebP optimized image path');
            $table->string('alt_text')->comment('SEO alt text (required)');
            $table->string('title_text')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['tour_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_media_gallery');
    }
};
