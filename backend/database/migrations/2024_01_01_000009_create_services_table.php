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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('url', 255);
            $table->string('page_title', 255);
            $table->string('page_description', 255);
            $table->string('main_image', 255)->nullable();
            $table->boolean('show_slider')->default(false);
            $table->string('thumbnail', 255)->default('miniatura.png');
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('reviews')->nullable();
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->foreignId('service_code_id')->constrained('service_codes')->onDelete('cascade');
            $table->string('location', 128)->nullable();
            $table->string('uri', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
