<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->string('name', 100);
            $table->string('review_date', 50)->nullable();
            $table->tinyInteger('rating')->default(5);
            $table->string('title', 255)->nullable();
            $table->text('comment');
            $table->string('language', 5)->default('en');
            $table->string('opinion', 255)->nullable(); // original tour name from source
            $table->boolean('published')->default(true);
            $table->boolean('featured')->default(false); // for showing on home page
            $table->timestamps();

            $table->index(['tour_id', 'published']);
            $table->index(['rating', 'published']);
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
