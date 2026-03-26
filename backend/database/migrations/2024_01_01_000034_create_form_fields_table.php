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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->text('field_name')->nullable();
            $table->text('field_name_attr')->nullable();
            $table->text('field_type')->nullable();
            $table->text('field_placeholder')->nullable();
            $table->text('field_value')->nullable();
            $table->text('field_values')->nullable();
            $table->integer('field_priority')->nullable();
            $table->foreignId('field_category_id')->constrained('field_categories')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
