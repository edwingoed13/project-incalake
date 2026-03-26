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
        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories_new')->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->string('name')->comment('Nombre traducido de la categoría');
            $table->text('description')->nullable()->comment('Descripción en el idioma específico');
            $table->string('slug', 150)->comment('Slug SEO para URLs');
            $table->timestamps();

            // Índices para optimización de queries
            $table->index(['category_id', 'language_id']);
            $table->index('slug');

            // Restricción única: una categoría solo puede tener una traducción por idioma
            $table->unique(['category_id', 'language_id'], 'category_language_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
