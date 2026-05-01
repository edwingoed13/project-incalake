<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->json('translations')->nullable()->comment('Map of {language_code: name} e.g. {"ES":"Uros","EN":"Uros"}');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tour_tag', function (Blueprint $table) {
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['tour_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_tag');
        Schema::dropIfExists('tags');
    }
};
