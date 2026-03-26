<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_translation_id')->constrained('tour_translations')->onDelete('cascade');
            $table->string('keyword', 100);
            $table->boolean('is_primary')->default(false)->comment('Primary keyword for SEO');
            $table->timestamps();
            
            $table->index(['tour_translation_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_keywords');
    }
};
