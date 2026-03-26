<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_schema_markup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_translation_id')->constrained('tour_translations')->onDelete('cascade');
            $table->enum('schema_type', ['TouristTrip', 'Product', 'Offer', 'AggregateRating', 'FAQPage'])->default('TouristTrip');
            $table->json('schema_json')->comment('Auto-generated Schema.org JSON-LD');
            $table->boolean('auto_generate')->default(true)->comment('Auto-generate from tour data');
            $table->timestamps();
            
            $table->index(['tour_translation_id', 'schema_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_schema_markup');
    }
};
