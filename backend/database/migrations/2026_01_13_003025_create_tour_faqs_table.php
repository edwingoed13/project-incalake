<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_translation_id')->constrained('tour_translations')->onDelete('cascade');
            $table->text('question');
            $table->text('answer');
            $table->integer('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['tour_translation_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_faqs');
    }
};
