<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page', 50)->index(); // 'home', 'about', 'contact'
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->json('content'); // All section data as JSON
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->unique(['page', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
