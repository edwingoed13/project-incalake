<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->foreignId('age_stage_id')->constrained('age_stages')->onDelete('cascade');
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->onDelete('cascade');
            $table->enum('price_type', ['per_person', 'per_group', 'per_quantity'])->default('per_person');
            $table->decimal('amount', 10, 2)->comment('Price amount');
            $table->integer('min_quantity')->default(1)->comment('Minimum people/quantity');
            $table->integer('max_quantity')->nullable()->comment('Maximum people/quantity');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['tour_id', 'age_stage_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_prices');
    }
};
